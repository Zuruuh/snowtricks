<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use \DateTime;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Trick;
use App\Entity\Message;
use App\Entity\TrickVideos;
use App\Entity\TrickImages;

class AppFixtures extends Fixture
{
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }
    public function load(ObjectManager $em)
    {
        $faker = Factory::create('fr_FR');

        $me = (new User())
            ->setUsername("Zuruh")
            ->setEmail("younesziadi@outlook.fr")
            ->setRegisterDate(new \DateTime())
            ->setIsVerified(true)
        ;

        $password = $this->encoder->encodePassword($me, 'aaaaaaaa');
        $me->setPassword($password);
        $em->persist($me);

        for ($i = 0; $i < 5; $i++) {
            $user = (new User())
                ->setUsername($faker->firstName() . $faker->lastName())
                ->setEmail($faker->email())
                ->setPassword($faker->password())
                ->setRegisterDate(new \DateTime())
                ->setIsVerified(true)
            ;
            $em->persist($user);

            $category = (new Category())
                ->setName($i . $faker->word())
                ->setCreator($user)
            ;
            $em->persist($category);
            for ($j = 0; $j < 5; $j++) {
                $trick = (new Trick())
                    ->setAuthor($user)
                    ->setCategory($category)
                    ->setName($faker->sentence(8))
                    ->setSlug($faker->randomDigitNot(0) . $faker->randomDigitNot(0) . "-" . $faker->slug())
                    ->setOverview($faker->text(256))
                    ->setDescription($faker->text(1024))
                    ->setThumbnail("/static/assets/default_thumbnail.jpg")
                    ->setPostDate(new \DateTime())
                    ->setLastUpdate(new \DateTime())
                ;
                for ($k = 0; $k < 3; $k++) {
                    $video = (new TrickVideos())
                        ->setTrick($trick)
                        ->setProvider($faker->randomElement(["youtube", "vimeo"]))
                        ->setUrl("" . $faker->randomNumber(8))
                    ;
                    $em->persist($video);
                    
                    $image = (new TrickImages())
                    ->setTrick($trick)
                    ->setPath("/static/assets/default_thumbnail.jpg")
                    ;
                    $em->persist($image);
                }

                for ($k = 0; $k < 12; $k++) {
                    $message = (new Message())
                        ->setAuthor($user)
                        ->setPost($trick)
                        ->setContent($faker->text(256))
                        ->setPostDate(new \DateTime())
                        ->setLastUpdate(new \DateTime())
                    ;
                    $em->persist($message);
                }
                $em->persist($trick);
            }
        }
        $em->flush();
    }
}
