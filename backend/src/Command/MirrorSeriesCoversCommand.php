<?php

namespace App\Command;

use App\Entity\Series;
use App\Exception\AnilistUnavailableException;
use App\Repository\SeriesRepository;
use App\Services\SeriesRefresher;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'app:series:mirror-covers', description: 'Copia a R2 las portadas que siguen sirviéndose desde AniList')]
class MirrorSeriesCoversCommand extends Command
{
    private const int SLEEP_MS = 1000;
    private const int DEFAULT_LIMIT = 200;
    private const array ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly SeriesRepository $seriesRepository,
                                private readonly HttpClientInterface $httpClient, private readonly FilesystemOperator $seriesImagesStorage,
                                private readonly SeriesRefresher $seriesRefresher,
                                #[Autowire('%env(R2_PUBLIC_URL)%')] private readonly string $cdnBaseUrl)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Número máximo de portadas a copiar en esta pasada', self::DEFAULT_LIMIT);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $series = $this->seriesRepository->findSeriesPendingCoverMirror(max(1, (int) $input->getOption('limit')));
        $total = count($series);
        if ($total === 0) {
            $io->success('No hay portadas pendientes de copiar');
            return Command::SUCCESS;
        }

        $io->writeln(sprintf('%d portadas, copiando con sleep de %dms entre descargas', $total, self::SLEEP_MS));
        $downloaded = 0;
        $reused = 0;
        $repaired = 0;
        $failed = 0;

        $io->progressStart($total);
        foreach ($series as $index => $serie) {
            $key = $this->buildKey($serie);
            $hitAnilist = true;
            try {
                if ($this->seriesImagesStorage->fileExists($key)) {
                    $hitAnilist = false;
                    $reused++;
                } else {
                    $staleUrl = $serie->getPortraitUrl();
                    $key = $this->downloadCoverRefreshingIfGone($serie, $key);
                    $downloaded++;
                    if ($serie->getPortraitUrl() !== $staleUrl) {
                        $repaired++;
                    }
                }
                $serie->setPortraitMirrorUrl(rtrim($this->cdnBaseUrl, '/') . '/series/' . $key);
            } catch (HttpExceptionInterface | FilesystemException | AnilistUnavailableException $exception) {
                $io->writeln(sprintf("\n  %d: %s", $serie->getAnilistId(), $exception->getMessage()));
                $failed++;
            }
            $io->progressAdvance();
            if ($hitAnilist && $index < $total - 1) {
                usleep(self::SLEEP_MS * 1000);
            }
        }
        $this->entityManager->flush();
        $io->progressFinish();

        $io->table(['Métrica', 'Cantidad'], [['Procesadas', $total], ['Descargadas', $downloaded], ['Ya estaban en R2', $reused],
            ['URL caducada reparada', $repaired], ['Fallidas', $failed]]);

        return $failed === $total ? Command::FAILURE : Command::SUCCESS;
    }
    
    private function downloadCoverRefreshingIfGone(Series $series, string $key): string
    {
        try {
            $this->downloadCover($series->getPortraitUrl(), $key);

            return $key;
        } catch (ClientExceptionInterface $exception) {
            if ($exception->getResponse()->getStatusCode() !== Response::HTTP_NOT_FOUND) {
                throw $exception;
            }
        }

        $this->seriesRefresher->refreshFromAnilist($series);
        $refreshedKey = $this->buildKey($series);
        $this->downloadCover($series->getPortraitUrl(), $refreshedKey);

        return $refreshedKey;
    }

    private function downloadCover(string $sourceUrl, string $key): void
    {
        $this->seriesImagesStorage->write($key, $this->httpClient->request('GET', $sourceUrl)->getContent());
    }

    private function buildKey(Series $series): string
    {
        return $series->getAnilistId() . '.' . $this->resolveExtension($series->getPortraitUrl());
    }

    private function resolveExtension(string $sourceUrl): string
    {
        $extension = mb_strtolower(pathinfo(parse_url($sourceUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));

        return in_array($extension, self::ALLOWED_EXTENSIONS, true) ? $extension : 'jpg';
    }
}
