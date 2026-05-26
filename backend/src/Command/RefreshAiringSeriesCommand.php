<?php

namespace App\Command;

use App\Enum\SeriesStatus;
use App\Exception\AnilistUnavailableException;
use App\Repository\SeriesRepository;
use App\Services\SeriesRefresher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:series:refresh-airing', description: 'Refresca series RELEASING con episodio pendiente y NOT_YET_RELEASED stale (modo cron diario)')]
class RefreshAiringSeriesCommand extends Command
{
    private const int SLEEP_MS = 2000;

    public function __construct(private readonly EntityManagerInterface $em, private readonly SeriesRepository $seriesRepository,
                                private readonly SeriesRefresher $seriesRefresher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('all', null, InputOption::VALUE_NONE, 'Refresca todas las RELEASING y NOT_YET_RELEASED, ignorando los filtros de freshness');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $series = $input->getOption('all') ? $this->seriesRepository->findBy(['airingStatus' => [SeriesStatus::RELEASING->value, SeriesStatus::NOT_YET_RELEASED->value]]) :
            $this->seriesRepository->findDueForAutoRefresh();

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
                $this->seriesRefresher->refresh($serie);
                if ($serie->getAiringStatus() !== $previousStatus) {
                    $changed++;
                }
            } catch (AnilistUnavailableException) {
                $failed++;
            }
            $io->progressAdvance();
            if ($index < $total - 1) {
                usleep(self::SLEEP_MS * 1000);
            }
        }
        $this->em->flush();
        $io->progressFinish();

        $io->table(['Métrica', 'Cantidad'], [['Procesadas', $total], ['Cambiaron de estado', $changed], ['Fallidas', $failed]]);

        return Command::SUCCESS;
    }
}
