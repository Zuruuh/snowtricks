<?php

namespace App\Entity;

use App\Repository\TrickRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

use \DateTime;

/**
 * @ORM\Entity(repositoryClass=TrickRepository::class)
 * @ORM\Table(name="trick", indexes={@ORM\Index(columns={"name", "description", "overview"}, flags={"fulltext"})})
 */
class Trick
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="tricks")
     * @ORM\JoinColumn(nullable=false)
     */
    private $author;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="tricks")
     */
    private $category;

    /**
     * @ORM\Column(type="string", length=128, unique=true)
     */
    private $name;
    
    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $slug;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $overview;
    
    // https://developer.mozilla.org/fr/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types
    // #[Assert\File(
    //     maxSize: "8192k",
    //     maxSizeMessage: "Your image is too heavy! Max image size is 8Mb",
    //     mimeTypes: ["image/jpeg", "image/png"],
    //     mimeTypesMessage: "Please upload a valid image (.jpeg or .png)"
    // )]
    // protected $thumbnail;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $thumbnail = "/static/assets/default_thumbnail.jpg";

    /**
     * @ORM\OneToMany(targetEntity=TrickVideos::class, mappedBy="trick", orphanRemoval=true)
     */
    private $videos;

    /**
     * @ORM\OneToMany(targetEntity=TrickImages::class, mappedBy="trick", orphanRemoval=true)
     */
    private $images;

    /**
     * @ORM\Column(type="datetime")
     */
    private $post_date;

    /**
     * @ORM\Column(type="datetime")
     */
    private $last_update;

    /**
     * @ORM\OneToMany(targetEntity=Message::class, mappedBy="post")
     */
    private $messages;

    public function __construct()
    {
        $this->messages    = new ArrayCollection();
        $this->post_date   = new \DateTime();
        $this->last_update = new \DateTime();
        $this->videos = new ArrayCollection();
        $this->images = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getOverview(): ?string
    {
        return $this->overview;
    }

    public function setOverview(?string $overview): self
    {
        $this->overview = $overview;

        return $this;
    }
    
    public function setThumbnail(?string $thumbnail): self
    {
        $this->thumbnail = $thumbnail;
        
        return $this;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    // public function getThumbnailPath(): ?string
    // {
    //     return $this->thumbnail_path;
    // }

    // public function setThumbnailPath(string $thumbnail_path): self
    // {
    //     $this->thumbnail_path = $thumbnail_path;

    //     return $this;
    // }

    public function getPostDate(): ?\DateTimeInterface
    {
        return $this->post_date;
    }

    public function setPostDate(\DateTimeInterface $post_date): self
    {
        $this->post_date = $post_date;

        return $this;
    }

    public function getLastUpdate(): ?\DateTimeInterface
    {
        return $this->last_update;
    }

    public function setLastUpdate(\DateTimeInterface $last_update): self
    {
        $this->last_update = $last_update;

        return $this;
    }

    /**
     * @return Collection|Message[]
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setPost($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getPost() === $this) {
                $message->setPost(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TrickVideos[]
     */
    public function getVideos(): Collection
    {
        return $this->videos;
    }

    public function addVideo(TrickVideos $video): self
    {
        if (!$this->videos->contains($video)) {
            $this->videos[] = $video;
            $video->setTrick($this);
        }

        return $this;
    }

    public function removeVideo(TrickVideos $video): self
    {
        if ($this->videos->removeElement($video)) {
            // set the owning side to null (unless already changed)
            if ($video->getTrick() === $this) {
                $video->setTrick(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TrickImages[]
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImages(TrickImages $image): self
    {
        if (!$this->images->contains($image)) {
            $this->images[] = $image;
            $image->setTrick($this);
        }

        return $this;
    }

    public function removeImages(TrickImages $image): self
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getTrick() === $this) {
                $image->setTrick(null);
            }
        }

        return $this;
    }
}
