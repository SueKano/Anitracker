<?php

namespace App\Command;

use App\Entity\Series;
use App\Exception\AnilistUnavailableException;
use App\Repository\SeriesRepository;
use App\Services\AnilistApiClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:series:sync-airing-window', description: 'Sincroniza el calendario de todas las series en emisión con una consulta por lote, detectando los episodios aplazados')]
class SyncAiringWindowCommand extends Command
{
    private const int DEFAULT_WINDOW_DAYS = 7;

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly SeriesRepository $seriesRepository,
                                private readonly AnilistApiClient $anilistClient)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('days', null, InputOption::VALUE_REQUIRED, 'Días de la ventana a sincronizar', self::DEFAULT_WINDOW_DAYS);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $windowDays = max(1, (int) $input->getOption('days'));

        $series = $this->seriesRepository->findSeriesForAiringWindowSync();
        if ($series === []) {
            $io->success('No hay series en emisión que sincronizar');

            return Command::SUCCESS;
        }

        $windowStart = time();
        $windowEnd = $windowStart + $windowDays * 86400;
        $anilistIds = array_map(static fn (Series $serie) => $serie->getAnilistId(), $series);

        try {
            $nodesByAnilistId = $this->anilistClient->fetchAiringWindow($anilistIds, $windowStart, $windowEnd);
        } catch (AnilistUnavailableException) {
            $io->error('AniList no respondió, el calendario guardado se mantiene intacto');

            return Command::FAILURE;
        }

        $updated = 0;

        foreach ($series as $serie) {
            if (!$serie->mergeAiringWindow($nodesByAnilistId[$serie->getAnilistId()] ?? [], $windowStart, $windowEnd)) {
                continue;
            }
            $updated++;
            $nextAiringAt = $serie->getNextAiringAt();
            $io->writeln(sprintf('  %s → %s', $serie->getRomajiName(), $nextAiringAt?->format('d/m/Y H:i') ?? 'sin próximo episodio conocido'));
        }
        $this->entityManager->flush();

        $io->table(['Métrica', 'Cantidad'], [['Ventana', $windowDays . ' días'], ['Series consultadas', count($series)], ['Calendarios actualizados', $updated]]);

        return Command::SUCCESS;
    }
}
