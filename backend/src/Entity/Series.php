<?php

namespace App\Entity;

use App\Enum\SeriesStatus;
use App\Repository\SeriesRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

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
    private ?string $portraitUrl = null;

    #[ORM\Column(type:'text', nullable: true)]
    private ?string $portraitMirrorUrl = null;

    #[ORM\Column(type:'string', length: 20, nullable: true)]
    #[Groups(['home:userSeries', 'detail:series', 'search:series'])]
    private string $airingStatus;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['home:userSeries', 'detail:series', 'search:series'])]
    private int $totalEpisodes = 0;
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

    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    private array $tags = [];

    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    private array $studios = [];

    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    private array $airingSchedule = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $lastRefreshedAt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['home:userSeries', 'detail:series', 'search:series'])]
    private bool $isAdult = false;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $idMal = null;

    public function getIdMal(): ?int
    {
        return $this->idMal;
    }

    public function setIdMal(?int $idMal): Series
    {
        $this->idMal = $idMal;
        return $this;
    }

    public function getIsAdult(): bool
    {
        return $this->isAdult;
    }

    public function setIsAdult(bool $isAdult): Series
    {
        $this->isAdult = $isAdult;
        return $this;
    }

    public function getLastRefreshedAt(): ?\DateTime
    {
        return $this->lastRefreshedAt;
    }

    public function setLastRefreshedAt(?\DateTime $lastRefreshedAt): Series
    {
        $this->lastRefreshedAt = $lastRefreshedAt;
        return $this;
    }

    public function getStudios(): array
    {
        return $this->studios;
    }

    public function setStudios(array $studios): Series
    {
        $this->studios = $studios;
        return $this;
    }

    public function getAiringSchedule(): array
    {
        return $this->airingSchedule;
    }

    public function getLastAiredEpisodeFromSchedule(): int
    {
        $now = time();
        $airedEpisodes = array_filter($this->airingSchedule, static fn (array $node) => $node['airingAt'] <= $now);

        return max([0, ...array_column($airedEpisodes, 'episode')]);
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): Series
    {
        $this->tags = $tags;
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

    public function getTotalEpisodes(): int
    {
        return $this->totalEpisodes;
    }

    public function setTotalEpisodes(int $totalEpisodes): Series
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

    public function getPortraitMirrorUrl(): ?string
    {
        return $this->portraitMirrorUrl;
    }

    public function setPortraitMirrorUrl(?string $portraitMirrorUrl): Series
    {
        $this->portraitMirrorUrl = $portraitMirrorUrl;
        return $this;
    }

    #[Groups(['recap:series', 'userProfile:series', 'home:userSeries', 'detail:series', 'search:series'])]
    #[SerializedName('portraitUrl')]
    public function getDisplayPortraitUrl(): ?string
    {
        return $this->portraitMirrorUrl ?? $this->portraitUrl;
    }

    public function mapAnilistData(array $media): self
    {
        $this->anilistId = $media['id'];
        $this->romajiName = $media['title']['romaji'];
        $this->englishName = $media['title']['english'] ?? '';
        $this->portraitUrl = $media['coverImage']['extraLarge'];
        $this->airingStatus = $media['status'];
        $this->genres = $media['genres'] ?? [];
        $this->tags = $media['tags'] ?? [];
        $this->studios = self::resolveMainStudios($media);
        $this->source = $media['source'] ?? '';
        $this->season = $media['season'] ?? null;
        $this->seasonYear = $media['seasonYear'] ?? 0;
        $this->format = $media['format'] ?? '';
        $this->synonyms = array_values(array_unique([...$this->synonyms, ...($media['synonyms'] ?? [])]));
        $this->idMal = $media['idMal'] ?? null;
        $this->isAdult = $media['isAdult'] ?? false;
        $this->totalEpisodes = max($this->totalEpisodes, self::resolveTotalEpisodes($media));

        if (isset($media['airingSchedule']['nodes'])) {
            $this->airingSchedule = array_values(array_map(
                static fn (array $node) => ['episode' => $node['episode'], 'airingAt' => $node['airingAt']], $media['airingSchedule']['nodes']
            ));
        }

        $next = $media['nextAiringEpisode'] ?? null;
        $this->currentAiringEpisode = match (true) {
            $next !== null => $next['episode'] - 1,
            $this->airingStatus === SeriesStatus::FINISHED->value => $this->totalEpisodes,
            default => self::resolveLastAiredEpisode($media),
        };

        if ($this->airingStatus === SeriesStatus::NOT_YET_RELEASED->value && $this->currentAiringEpisode >= 1 && $this->totalEpisodes !== $this->currentAiringEpisode) {
            $this->airingStatus = SeriesStatus::RELEASING->value;
        }

        $airingAt = $next['airingAt'] ?? null;
        $this->nextAiringAt = $airingAt ? new \DateTime()->setTimestamp($airingAt) : null;
        $this->airingDay = ($airingAt && !$this->isAdult && $this->airingStatus === SeriesStatus::RELEASING->value) ?
            strtoupper(new DateTimeImmutable('@'.$airingAt)->setTimezone(new \DateTimeZone('Europe/Madrid'))->format('l')) : null;

        return $this;
    }

    public static function createSeriesFromAnilistData(array $data): self
    {
        return new self()->mapAnilistData($data);
    }

    public static function resolveMainStudios(array $data): array
    {
        $mainEdges = array_filter($data['studios']['edges'] ?? [], static fn (array $edge) => $edge['isMain']);

        return array_values(array_map(static fn (array $edge) => $edge['node']['name'], $mainEdges));
    }

    public static function resolveTotalEpisodes(array $data): int
    {
        if ($data['episodes'] > 0) {
            return $data['episodes'];
        }
        $nodes = $data['airingSchedule']['nodes'] ?? [];

        return max([0, ...array_column($nodes, 'episode')]);
    }

    public static function resolveLastAiredEpisode(array $data): int
    {
        $now = time();
        $airingEpisodes = array_filter($data['airingSchedule']['nodes'] ?? [], static fn (array $node) => $node['airingAt'] <= $now);

        return max([0, ...array_column($airingEpisodes, 'episode')]);
    }
}
