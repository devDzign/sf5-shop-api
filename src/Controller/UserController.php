<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class UserController
 * @package App\Controller
 * @Route("/api")
 */
class UserController extends AbstractController
{

    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ) {
        $this->serializer    = $serializer;
        $this->validator     = $validator;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/users", name="user.registration", methods={"POST"})
     */
    public function registration( Request $request )
    {
        $data = $request->getContent();

        $user = $this->serializer->deserialize(
            $data,
            User::class,
            'json',
            [
                ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
            ]
        );


        $errors = $this->validator->validate($user, null, ["add.user"]);

        $errorsResponse = ['errors' => [], 'code' => 400];

        if ( $errors->count() > 0 ) {

            foreach ($errors as $error) {
                /** @var ConstraintViolation $error */
                $message  = $error->getMessage();
                $property = $error->getPropertyPath();

                $errorsResponse['errors'][$property][] = $message;
            }

            return $this->json(
                $errorsResponse
                ,
                400
            );
        }


        $this->entityManager->persist($user);

        $this->entityManager->flush();

        return $this->json(
            $user,
            201,
            [],
            [
                "groups" => ["registration"],
            ]
        );
    }


    /**
     * @Route("/users/{idUser}/image", name="add_image_for_user", methods={"PUT"})
     * @return JsonResponse
     */
    public function addImageForUser( User $user = null, Request $request )
    {
        if ( is_null($user) ) {
            $dataError = [
                "messsage" => "user not found with this id : ".$request->get("idUser"),
                "code"     => 400,

            ];

            return $this->json(
                $dataError
                ,
                400
            );
        }

        // je recupere encienne image
        $oldImage = $user->getImage();

        $options = [
            ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true
        ];

        if($oldImage !== null){
            // encienne image existe alor je l'a mise à jour  [
            //   ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
            //   ObjectNormalizer::OBJECT_TO_POPULATE => $oldImage
            // ]
            //
          $options[ObjectNormalizer::OBJECT_TO_POPULATE] =  $oldImage;
        }

        $image =  $image = $this->serializer->deserialize(
            $request->getContent(),
            Image::class,
            'json',
            $options
        );

        $errors = $this->validator->validate($image);

        if ( $errors->count() > 0 ) {
            $dataError = [
                "messsage" => "Some Errors in body image",
                "code"     => 400,
            ];

            return $this->json(
                $dataError
                ,
                400
            );
        }

        $user->setImage($image);

        $this->entityManager->persist($image);
        $this->entityManager->flush();

        return $this->json($user, 200);
    }
}
