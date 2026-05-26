<?php

namespace App\Controller;

use App\Entity\User;
use App\Services\RecapBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RecapController extends AbstractController
{
    #[Route('/api/recap/{year}', name: 'recap_by_year_and_user', requirements: ['year' => '\d+'], methods: ['GET'])]
    public function getRecapByYearAndUser(int $year, RecapBuilder $builder) :Response
    {
        $user = $this->getUser();
        assert($user instanceof User);

        return $this->json($builder->buildRecap($user, $year), Response::HTTP_OK, [], ['groups' => ['recap:userSeries', 'recap:series']]);
    }
}
