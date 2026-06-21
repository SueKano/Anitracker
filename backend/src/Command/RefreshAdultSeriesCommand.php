<?php

namespace App\Command;

use App\Exception\JikanUnavailableException;
use App\Repository\SeriesRepository;
use App\Services\SeriesRefresher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:series:adult-refresh', description: 'Refresca total episodes y estado de series adultas vía MyAnimeList')]
class RefreshAdultSeriesCommand extends Command
{
    private const int SLEEP_MS = 2000;

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly SeriesRepository $seriesRepository,
                                private readonly SeriesRefresher $seriesRefresher)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $series = $this->seriesRepository->findAdultSeriesForAutoRefresh();

        $total = count($series);
        if ($total === 0) {
            $io->success('No hay series que refrescar');
            return Command::SUCCESS;
        }

        $io->writeln(sprintf('%d series, refrescando con sleep de %dms entre llamadas', $total, self::SLEEP_MS));
        $changed = 0;
        $failed = 0;

        $io->progressStart($total);
        foreach ($series as $index => $serie) {
            $previousStatus = $serie->getAiringStatus();
            try {
                $this->seriesRefresher->refreshFromMal($serie);
                if ($serie->getAiringStatus() !== $previousStatus) {
                    $changed++;
                }
            } catch (JikanUnavailableException) {
                $failed++;
            }
            $io->progressAdvance();
            if ($index < $total - 1) {
                usleep(self::SLEEP_MS * 1000);
            }
        }
        $this->entityManager->flush();
        $io->progressFinish();

        $io->table(['Métrica', 'Cantidad'], [['Procesadas', $total], ['Cambiaron de estado', $changed], ['Fallidas', $failed]]);

        return Command::SUCCESS;
    }
}
