<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class ArticleController
 * @package App\Controller
 * @Route("/api")
 */
class ArticleController extends AbstractController
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
     * @Route("/articles", name="article_created", methods={"POST"})
     */
    public function create( Request $request )
    {
        $data = $request->getContent();

        $article = $this->serializer->deserialize(
            $data,
            Article::class,
            'json',
            [
                ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
            ]
        );

        $errors = $this->validator->validate($article);

        if ( $errors->count() > 0 ) {

            return $this->json(
                [
                    "message" => "Some errors ditcted in your request",
                    "code"    => 400,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        return $this->json($article, Response::HTTP_OK);

    }

    /**
     * @Route("/articles/{id}/comment", name="article_created", methods={"PUT"})
     */
    public function addCommentsForArticle( Request $request, Article $article = null )
    {

        if ( is_null($article) ) {
            return $this->json(
                [
                    "message" => "you tried add comment for article doesn't exist",
                    "code"    => Response::HTTP_NOT_FOUND,
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        $comment = $this->serializer->deserialize(
            $request->getContent(),
            Comment::class,
            'json',
            [
                ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
            ]
        );

        $errors = $this->validator->validate($comment);

        if($errors->count() > 0) {

            return $this->json(
                [
                    "message" => "Some errors in body to create comment",
                    "code"    => Response::HTTP_BAD_REQUEST,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $article->addComment($comment);

        $this->entityManager->flush();

        return $this->json(
            $article,
            200,
            [],
            [
                "groups"=> ["read"]
            ]
        );
    }
}
