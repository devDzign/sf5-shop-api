<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class ProductController
 * @package App\Controller
 * @Route("/api")
 */
class ProductController extends AbstractController
{
    /**
     * @Route("/products", name="create.product.post", methods={"POST"})
     */
    public function create(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
       ValidatorInterface $validator
    )
    {
        // je recupere les donnes envoyé pas utilisateur

        $data = $request->getContent();

        // je desrialize mes donne dans mon objet a cree

        $product =  $serializer->deserialize(
            $data,
            Product::class,
            'json',
            [
                ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true
            ]
        );

        // je valide si les contraites sont respectées

        $errors = $validator->validate($product);

        if($errors->count()> 0){

            return $this->json([
                "message"=>" des erreurs sont detectées sur votre objet envoyés",
                "code" => 400
            ],
                400
            );
        }

        // si tous va bien je enrgistre et je donne reponse ok
        $entityManager->persist($product);
        $entityManager->flush();


        return $this->json($product, 201);
    }



    /**
     * @Route("/products/{id}", name="update.product.put", methods={"PUT"})
     */
    public function update(
        Product $product = null,
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    )
    {

        if(is_null($product)){

            return $this->json(
               "le produit avec cette id n'existe pas",
               400
            );
        }


        // je recupere les donnes envoyé pas utilisateur

        $data = $request->getContent();

        // je desrialize mes donne dans mon objet a cree

          $serializer->deserialize(
            $data,
            Product::class,
            'json',
            [
                ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
                ObjectNormalizer::OBJECT_TO_POPULATE => $product
            ]
        );

        // je valide si les contraites sont respectées

        $errors = $validator->validate($product);

        if($errors->count()> 0){

            return $this->json([
                "message"=>" des erreurs sont detectées sur votre objet envoyés",
                "code" => 400
            ],
                400
            );
        }

        // si tous va bien je enrgistre et je donne reponse ok
        $entityManager->persist($product);
        $entityManager->flush();


        return $this->json($product, 200);
    }
}