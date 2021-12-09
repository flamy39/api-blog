<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\PostRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PostController extends AbstractController
{

    private PostRepository $postRepository;
    private SerializerInterface $serializer;
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    /**
     * PostController constructor.
     * @param PostRepository $postRepository
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     * @param ValidatorInterface $validator
     */
    public function __construct(PostRepository $postRepository,
                                SerializerInterface $serializer,
                                EntityManagerInterface $entityManager,
                                ValidatorInterface $validator)
    {
        $this->postRepository = $postRepository;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    /**
     * @Route("/api/posts", name="api_post_getposts", methods={"GET"})
     * @return Response
     */
    public function getPosts(): Response
    {
        $hasRoleAdmin = $this->isGranted('ROLE_ADMIN');
        if ($hasRoleAdmin) {
            $posts = $this->postRepository->findAll();
        } else {
            $user = $this->getUser();
            $posts = $this->postRepository->findBy(['user' => $user]);
        }
        $postsJson = $this->serializer->serialize($posts,'json',['groups' => 'post_read']);
        // Génération de la réponse
        $response = new Response();
        $response->setStatusCode(200,"OK");
        $response->headers->set("content-type","application/json");
        $response->setContent($postsJson);
        return $response;
    }

    /**
     * @Route("/api/posts/{id}", name="api_post_getpost", methods={"GET"})
     * @param int $id
     * @return Response
     */
    public function getPost(int $id): Response
    {
        $post = $this->postRepository->find($id);
        if (!$post){
            $error = [
                "status" =>Response::HTTP_NOT_FOUND,
                "message" => "Le post demandé n'existe pas"
            ];
            return new Response(json_encode($error),
                    Response::HTTP_NOT_FOUND ,["content-type"=>"application/json"]);
        }
        $postJson = $this->serializer->serialize($post,'json',['groups' => 'post_read']);
        // Génération de la réponse
        return new Response($postJson,Response::HTTP_OK,["content-type"=>"application/json"]);
    }

    /**
     * @Route("/api/posts",name="api_post_create",methods={"POST"})
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createPost(Request $request): Response {
        $postJson = $request->getContent();
        $user = $this->getUser();

        // Mettre en surveillance le bloc de code
        try {
            // Désérialise le JSON en un objet de la classe Post
            $post = $this->serializer->deserialize($postJson,Post::class,'json');
            // Validation du post
            $response = $this->validatePost($post);
            if (!is_null($response)) {
                return $response;
            }
            // Insertion dans la base de données
            $post->setCreatedAt(new DateTime());
            $post->setUser($user);
            $this->entityManager->persist($post);
            $this->entityManager->flush();
            // Génération de la réponse au format Json
            $postJson = $this->serializer->serialize($post,'json',['groups' => 'post_read']);
            return new JsonResponse($postJson,Response::HTTP_CREATED,[],true);

        } // Intercepter l'exception et la traiter
        catch (NotEncodableValueException $exception) {
            $error = [
                "status" => Response::HTTP_BAD_REQUEST,
                "message" => $exception->getMessage()
            ];
            return new JsonResponse(json_encode($error),Response::HTTP_BAD_REQUEST,[],true);
        }
    }

    /**
     * @Route("/api/posts/{id}",name="api_post_deletepost", methods={"DELETE"})
     * @param int $id
     * @return Response
     */
    public function deletePost(int $id) : Response {
        // Recherche du post dans la base de données
        $post = $this->postRepository->find($id);
        // Suppression de la base de données
        $this->entityManager->remove($post);
        $this->entityManager->flush();
        // Génération de la réponse
        //$postJson = $this->serializer->serialize($post,'json');
        return new Response(null,Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/api/posts/{id}",name="api_post_updatepost", methods={"PUT"})
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function updatePost(Request $request, int $id) : Response {
        // Récupération du body de la requête
        $postJson = $request->getContent();
        // Recherche du post à modifier dans la base de données
        $post = $this->postRepository->find($id);
        // Modification du post avec les données du body
        $this->serializer->deserialize($postJson,Post::class,'json',["object_to_populate"=>$post]);
        // Modification dans la base de données
        $this->entityManager->flush();
        // Génération de la réponse
        return new Response(null,Response::HTTP_NO_CONTENT);
    }

    /**
     * @param Post $post
     * @return Response|null
     */
    private function validatePost(Post $post) : ?Response {
        $errors = $this->validator->validate($post);
        if (count($errors) > 0 ) {
            $errorsJson = $this->serializer->serialize($errors,'json');
            return new JsonResponse($errorsJson,Response::HTTP_BAD_REQUEST,[],true);
        }
        return null;
    }
}
