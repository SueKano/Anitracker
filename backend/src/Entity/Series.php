<?php

namespace App\Entity;

use App\Enum\SeriesStatus;
use App\Repository\SeriesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: SeriesRepository::class)]
class Series extends AbstractEntity
{
    #[ORM\Column(type:'string', nullable: true)]
    #[Groups(['detail:series', 'search:series'])]
    private ?string $englishName = null;
    #[ORM\Column(type:'string', nullable: false)]
    #[Groups(['recap:series', 'userProfile:series', 'home:userSeries', 'detail:series', 'search:series'])]
    private string $romajiName;

    #[ORM\Column(type:'text', nullable: true)]
    #[Groups(['recap:series', 'userProfile:series', 'home:userSeries', 'detail:series', 'search:series'])]
    private ?string $portraitUrl = null;

    #[ORM\Column(type:'string', length: 20, nullable: true)]
    #[Groups(['home:userSeries', 'detail:series', 'search:series'])]
    private string $airingStatus;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['home:userSeries', 'detail:series', 'search:series'])]
    private ?int $totalEpisodes = null;
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['home:userSeries', 'detail:series', 'search:series'])]
    private int $currentAiringEpisode = 0;

    #[ORM\Column(type:'string', length: 10, nullable: true)]
    #[Groups(['home:userSeries', 'detail:series', 'search:series'])]
    private ?string $airingDay =null;

    #[ORM\Column(type: 'integer', unique: true, options: ['default' => 0])]
    #[Groups(['userProfile:series', 'home:userSeries', 'detail:series', 'search:series'])]
    private int $anilistId = 0;

    #[ORM\Column(type:'json')]
    #[Groups(['home:userSeries', 'detail:series', 'search:series'])]
    private array $genres = [];

    #[ORM\Column(type:'string', length: 15)]
    #[Groups(['home:userSeries', 'detail:series', 'search:series'])]
    private string $format;

    #[ORM\Column(type:'string', length: 20)]
    #[Groups(['home:userSeries', 'detail:series', 'search:series'])]
    private string $source;
    #[ORM\Column(type:'string', length: 25, nullable: true)]
    #[Groups(['recap:series', 'home:userSeries', 'detail:series', 'search:series'])]
    private ?string $season = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['recap:series', 'home:userSeries', 'detail:series', 'search:series'])]
    private ?int $seasonYear = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $nextAiringAt = null;

    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    private array $synonyms = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $lastRefreshedAt = null;

    public function getLastRefreshedAt(): ?\DateTime
    {
        return $this->lastRefreshedAt;
    }

    public function setLastRefreshedAt(?\DateTime $lastRefreshedAt): Series
    {
        $this->lastRefreshedAt = $lastRefreshedAt;
        return $this;
    }

    public function getSynonyms(): array
    {
        return $this->synonyms;
    }

    public function setSynonyms(array $synonyms): Series
    {
        $this->synonyms = $synonyms;
        return $this;
    }

    public function getNextAiringAt(): ?\DateTime
    {
        return $this->nextAiringAt;
    }

    public function setNextAiringAt(?\DateTime $nextAiringAt): Series
    {
        $this->nextAiringAt = $nextAiringAt;
        return $this;
    }

    public function getSeasonYear(): ?int
    {
        return $this->seasonYear;
    }

    public function setSeasonYear(?int $seasonYear): Series
    {
        $this->seasonYear = $seasonYear;
        return $this;
    }


    public function getEnglishName(): ?string
    {
        return $this->englishName;
    }

    public function setEnglishName(?string $englishName): Series
    {
        $this->englishName = $englishName;
        return $this;
    }

    public function getRomajiName(): string
    {
        return $this->romajiName;
    }

    public function setRomajiName(string $romajiName): Series
    {
        $this->romajiName = $romajiName;
        return $this;
    }


    public function getSeason(): ?string
    {
        return $this->season;
    }

    public function setSeason(?string $season): Series
    {
        $this->season = $season;
        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $format): Series
    {
        $this->format = $format;
        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): Series
    {
        $this->source = $source;
        return $this;
    }

    public function getGenres(): array
    {
        return $this->genres;
    }

    public function setGenres(array $genre): Series
    {
        $this->genres = $genre;
        return $this;
    }

    public function getAnilistId(): int
    {
        return $this->anilistId;
    }

    public function setAnilistId(int $anilistId): Series
    {
        $this->anilistId = $anilistId;
        return $this;
    }

    public function getAiringDay(): ?string
    {
        return $this->airingDay;
    }

    public function setAiringDay(?string $airingDay): Series
    {
        $this->airingDay = $airingDay;
        return $this;
    }

    public function getCurrentAiringEpisode(): int
    {
        return $this->currentAiringEpisode;
    }

    public function setCurrentAiringEpisode(int $currentAiringEpisode): Series
    {
        $this->currentAiringEpisode = $currentAiringEpisode;
        return $this;
    }

    public function getTotalEpisodes(): ?int
    {
        return $this->totalEpisodes;
    }

    public function setTotalEpisodes(?int $totalEpisodes): Series
    {
        $this->totalEpisodes = $totalEpisodes;
        return $this;
    }

    public function getAiringStatus(): string
    {
        return $this->airingStatus;
    }

    public function setAiringStatus(string $airingStatus): Series
    {
        $this->airingStatus = $airingStatus;
        return $this;
    }

    public function getPortraitUrl(): ?string
    {
        return $this->portraitUrl;
    }

    public function setPortraitUrl(?string $portraitUrl): Series
    {
        $this->portraitUrl = $portraitUrl;
        return $this;
    }

    public static function mapAnilistMediaToArray(array $media): array
    {
        $airingDay = null;
        if ($media['status'] === SeriesStatus::RELEASING->value && !empty($media['airingSchedule']['nodes'][0]['airingAt'])) {
            $day = new \DateTimeImmutable('@' . $media['airingSchedule']['nodes'][0]['airingAt'])
                ->setTimezone(new \DateTimeZone('Europe/Madrid'))->format('l');
            $airingDay = strtoupper($day);
        }

        return [
            'anilistId'            => $media['id'],
            'romajiName'           => $media['title']['romaji'],
            'englishName'          => $media['title']['english'] ?? '',
            'portraitUrl'          => $media['coverImage']['extraLarge'],
            'airingStatus'         => $media['status'],
            'totalEpisodes'        => $media['episodes'] ?? 0,
            'currentAiringEpisode' => $media['nextAiringEpisode']['episode'] ?? 0,
            'airingDay'            => $airingDay,
            'genres'               => $media['genres'] ?? [],
            'source'               => $media['source'] ?? '',
            'season'               => $media['season'] ?? null,
            'seasonYear'           => $media['seasonYear'] ?? 0,
            'format'               => $media['format'] ?? '',
            'synonyms'             => $media['synonyms'] ?? [],
        ];
    }

    public static function createSeriesFromAnilistData(array $directData): self
    {
        $dto = self::mapAnilistMediaToArray($directData);

        $newSeries = new self();
        $newSeries->setAnilistId($dto['anilistId']);
        $newSeries->setRomajiName($dto['romajiName']);
        $newSeries->setEnglishName($dto['englishName']);
        $newSeries->setPortraitUrl($dto['portraitUrl']);
        $newSeries->setAiringStatus($dto['airingStatus']);
        $newSeries->setTotalEpisodes($dto['totalEpisodes']);
        $newSeries->setCurrentAiringEpisode($dto['currentAiringEpisode']);
        $newSeries->setAiringDay($dto['airingDay']);
        $newSeries->setGenres($dto['genres']);
        $newSeries->setSource($dto['source']);
        $newSeries->setSeason($dto['season']);
        $newSeries->setSeasonYear($dto['seasonYear']);
        $newSeries->setFormat($dto['format']);
        $newSeries->setSynonyms($dto['synonyms']);

        return $newSeries;
    }

}
