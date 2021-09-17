<?php

namespace App\Entity;

use App\Repository\TrickVideosRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TrickVideosRepository::class)
 */
class TrickVideos
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Trick::class, inversedBy="videos")
     * @ORM\JoinColumn(nullable=false)
     */
    private $trick;

    /**
     * @ORM\Column(type="string", length=512)
     */
    private $embed;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrick(): ?Trick
    {
        return $this->trick;
    }

    public function setTrick(?Trick $trick): self
    {
        $this->trick = $trick;

        return $this;
    }

    public function getEmbed(): ?string
    {
        return $this->embed;
    }

    public function setEmbed(string $embed): self
    {
        $this->embed = $embed;

        return $this;
    }
}
