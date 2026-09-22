<?php

namespace App\Command;

use App\Entity\Series;
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

#[AsCommand(name: 'app:series:refresh-airing', description: 'Refresca series RELEASING que emiten hoy y NOT_YET_RELEASED pendientes (modo cron diario)')]
class RefreshAiringSeriesCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly SeriesRepository $seriesRepository,
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

        $series = !$input->getOption('all') ? array_merge($this->seriesRepository->findAiringSeriesForAutoRefresh(), $this->seriesRepository->findFutureSeriesForAutoRefresh())
            : $this->seriesRepository->findBy(['airingStatus' => [SeriesStatus::RELEASING->value, SeriesStatus::NOT_YET_RELEASED->value]]);

        $total = count($series);
        if ($total === 0) {
            $io->success('No hay series que refrescar');
            return Command::SUCCESS;
        }

        $previousStatuses = [];
        foreach ($series as $serie) {
            $previousStatuses[$serie->getAnilistId()] = $serie->getAiringStatus();
        }

        try {
            $refreshed = $this->seriesRefresher->refreshManyFromAnilist($series);
        } catch (AnilistUnavailableException) {
            $io->error('AniList no respondió, no se ha refrescado nada');

            return Command::FAILURE;
        }

        $changed = count(array_filter($refreshed, static fn (Series $serie) => $serie->getAiringStatus() !== $previousStatuses[$serie->getAnilistId()]));
        $this->entityManager->flush();

        $io->table(['Métrica', 'Cantidad'], [['Procesadas', $total], ['Cambiaron de estado', $changed], ['No devueltas por AniList', $total - count($refreshed)]]);

        return Command::SUCCESS;
    }
}
