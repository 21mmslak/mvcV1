<?php

namespace App\Controller;

use App\Entity\Books;
use App\Repository\BooksRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

final class BookController extends AbstractController
{
    #[Route('/library', name: 'library')]
    public function viewAllProduct(
        BooksRepository $booksRepository
    ): Response {
        $books = $booksRepository->findAll();

        $data = [
            'books' => $books
        ];

        return $this->render('books/index.html.twig', $data);
    }
    // #[Route('/library', name: 'library')]
    // public function index(): Response
    // {
    //     return $this->render('books/index.html.twig', [
    //         'controller_name' => 'BooksController',
    //     ]);
    // }




    // #[Route('/library/create', name: 'book_create')]
    // public function create(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    // {
    //     $book = new Books();

    //     if ($request->isMethod('POST')) {
    //         $book->setTitel($request->request->get('titel'));
    //         $book->setISBN($request->request->get('ISBN'));
    //         $book->setFörfattare($request->request->get('författare'));

    //         /** @var UploadedFile $imageFile */
    //         $imageFile = $request->files->get('bild');

    //         if ($imageFile) {
    //             $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
    //             $safeFilename = $slugger->slug($originalFilename);
    //             $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

    //             $imageFile->move($this->getParameter('images_directory'), $newFilename);

    //             $book->setBild($newFilename);
    //         }

    //         $em->persist($book);
    //         $em->flush();

    //         return $this->redirectToRoute('library');
    //     }

    //     return $this->render('books/createForm.html.twig');
    // }



    #[Route('/library/create', name: 'book_create')]
    public function createBookForm(): Response
    {
        return $this->render('books/createForm.html.twig');
    }



    #[Route('/library/store', name: 'book_store', methods: ['POST'])]
    public function store(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $targetDir = $this->getParameter('images_directory');

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }
        if (!is_writable($targetDir)) {
            throw new \RuntimeException('Katalogen är inte skrivbar: ' . $targetDir);
        }
        $book = new Books();

        $book->setTitel($request->request->get('titel'));
        $book->setISBN($request->request->get('isbn'));
        $book->setFörfattare($request->request->get('författare'));

        /** @var UploadedFile $imageFile */
        $imageFile = $request->files->get('bild');
        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

            $imageFile->move($this->getParameter('images_directory'), $newFilename);

            // $book->setBild('/img/' . $newFilename);
            $book->setBild($newFilename);
        }

        $entityManager->persist($book);
        $entityManager->flush();

        return $this->redirectToRoute('library');
    }

    #[Route('/library/book/{id}', name: 'book_show')]
    public function showBook(BooksRepository $booksRepository, int $id): Response
    {
        $book = $booksRepository->find($id);

        if (!$book) {
            throw $this->createNotFoundException("Boken hittades inte.");
        }

        return $this->render('books/show.html.twig', [
            'book' => $book
        ]);
    }

    #[Route('/library/update/{id}', name: 'book_update', methods: ['GET'])]
    public function updateBook(BooksRepository $booksRepository, int $id): Response
    {
        $book = $booksRepository->find($id);

        if (!$book) {
            throw $this->createNotFoundException("Boken hittades inte.");
        }

        return $this->render('books/update.html.twig', [
            'book' => $book
        ]);
    }

    #[Route('/library/update/{id}', name: 'book_update_save', methods: ['POST'])]
    public function updateBookSave(
        Request $request,
        BooksRepository $booksRepository,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        int $id
    ): Response {
        $book = $booksRepository->find($id);

        if (!$book) {
            throw $this->createNotFoundException("Boken hittades inte.");
        }

        $book->setTitel($request->request->get('titel'));
        $book->setISBN($request->request->get('ISBN'));
        $book->setFörfattare($request->request->get('författare'));

        /** @var UploadedFile|null $imageFile */
        $imageFile = $request->files->get('bild');
        if ($imageFile) {
            $oldImage = $book->getBild();
            if ($oldImage && file_exists($this->getParameter('kernel.project_dir') . '/public' . $oldImage)) {
                unlink($this->getParameter('kernel.project_dir') . '/public' . $oldImage);
            }

            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

            $imageFile->move(
                $this->getParameter('images_directory'),
                $newFilename
            );

            // $book->setBild('/img/' . $newFilename);
            $book->setBild($newFilename);
        }

        $em->persist($book);
        $em->flush();

        return $this->redirectToRoute('library');
    }

    #[Route('/library/delete/{id}', name: 'book_delete')]
    public function deleteBook(BooksRepository $booksRepository, EntityManagerInterface $entityManager, int $id): Response
    {
        $book = $booksRepository->find($id);

        if (!$book) {
            throw $this->createNotFoundException("Boken hittades inte.");
        }

        $entityManager->remove($book);
        $entityManager->flush();

        return $this->redirectToRoute('library');
    }

    #[Route('/library/reset', name: 'book_reset')]
    public function resetLibrary(EntityManagerInterface $entityManager): Response
    {
        $entityManager->createQuery('DELETE FROM App\Entity\Books')->execute();

        $book1 = new Books();
        $book1->setTitel("A Brief History of Intelligence")
            ->setFörfattare("Max Bennett")
            ->setISBN("9780008560096")
            ->setBild("bok1.jpg");
            // ->setBild("/img/bok1.jpg");

        $book2 = new Books();
        $book2->setTitel("Be Useful")
            ->setFörfattare("Arnold Schwarzenegger")
            ->setISBN("9781529146530")
            ->setBild("bok2.jpg");
            // ->setBild("/img/bok2.jpg");

        $book3 = new Books();
        $book3->setTitel("Python från början")
            ->setFörfattare("Jan Skansholm")
            ->setISBN("9789144187617")
            ->setBild("bok3.jpg");
            // ->setBild("/img/bok3.jpg");

        $entityManager->persist($book1);
        $entityManager->persist($book2);
        $entityManager->persist($book3);
        $entityManager->flush();

        return $this->redirectToRoute('library');
    }
}
