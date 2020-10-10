<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
     * @Route("/users", name="user.registration", methods={"POST"})
     */
    public function registration(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ) {
        $data = $request->getContent();

        $user = $serializer->deserialize(
            $data,
            User::class,
            'json',
            [
                ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
            ]
        );


        $errors = $validator->validate($user, null, ["add.user"]);

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


        $entityManager->persist($user);

        $entityManager->flush();

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
     * @Route("/users/{id}/images", name="create.and.add.image.for.user", methods={"POST"})
     */
    public function craeteAndAddImageForUser(
        User $user = null,
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    )
    {

        if ( is_null($user) ) {

            $dataError =  [
                "messsage" => "user not found withh this id : ".$request->get("id"),
                "code"     => 400,

            ];
            return $this->json(
                $dataError
               ,
                400
            );
        }


        $image = $serializer->deserialize($request->getContent(), Image::class,'json',[
            ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT=> true
        ]);


        $errors =  $validator->validate($image);

        if($errors->count()>0) {
            $dataError =  [
                "messsage" => "Some Errors in body image",
                "code"     => 400,

            ];
            return $this->json(
                $dataError
                ,
                400
            );
        }

        $oldImage = $user->getImage();

        $user->setImage($image);

        if($oldImage){
            $entityManager->remove($oldImage);
        }

        $entityManager->flush();


        return $this->json($user, 200);
    }
}
