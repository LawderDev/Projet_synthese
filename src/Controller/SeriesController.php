<?php

namespace App\Controller;

use App\Entity\Episode;
use App\Entity\Rating;
use App\Entity\Series;
use App\Form\AscFormType;
use App\Form\DscFormType;
use App\Form\SerieRechearchType;
use App\Form\SeriesType;
use App\Form\RatingFormType;
use App\Repository\SeriesRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/series")
 */
class SeriesController extends AbstractController
{
    /**
     * @Route("/", name="series_indexSeries", methods={"GET","POST"})
     */
    public function series_indexSeries(Request $request, SeriesRepository $repository): Response
    {
        $buttonAsc = $this->createForm(AscFormType::class);
        $buttonAsc->handleRequest($request);
        $buttonDsc = $this->createForm(DscFormType::class);
        $buttonDsc->handleRequest($request);

        $formulaireRecherche = $this->createForm(SerieRechearchType::class);
        $formulaireRecherche->handleRequest($request);

        if ($formulaireRecherche->isSubmitted() && $formulaireRecherche->isValid()) {
            $text = $formulaireRecherche->get('recherche')->getData();

            $result = $repository->findWithTitle($text);

            $nbSeriesR = $repository->countWithTitle($text);
            $nbSeriePerPage=ROUND($nbSeriesR/10);
        } else {
            $nbSeries = $repository->countSeries();
            $nbSeriePerPage = ROUND($nbSeries/10); // combien de série par page

            if($buttonAsc->isSubmitted())
                $result = $repository->ascSeries();
            else if($buttonDsc->isSubmitted())
                $result = $repository->descSeries();
            else
                $result = $repository->findSeriesFirstPage();                  
        }
        return $this->render('series/index.html.twig', [
            'series' => $result,
            'button_asc' => $buttonAsc->createView(),
            'button_dsc' => $buttonDsc->createView(),
            'form' => $formulaireRecherche->createView(),
            'numberPage' => $nbSeriePerPage,
        ]);
    }

    /**
     * @Route("/number/{pageNumber}", name="series_index", methods={"GET","POST"})
     */
    public function index(Request $request, int $pageNumber, SeriesRepository $repository): Response
    {
        $formulaireRecherche = $this->createForm(SerieRechearchType::class);
        $formulaireRecherche->handleRequest($request);


        if(($formulaireRecherche->isSubmitted() && $formulaireRecherche->isValid())){
            $text = $formulaireRecherche->get('recherche')->getData();

            $result = $repository->findWithTitle($text);
            $nbSeriesR = $repository->countWithTitle($text);
            
            $nbSeriePerPage=ROUND($nbSeriesR/10);

            if($pageNumber == 1){
                $result = $repository->findSeriesFirstPageTitle($pageNumber,$text);
            }else{
                $result = $repository->findSeriesTitle($pageNumber,$text);
            }
        }
        else{
            $text = null;
            $nbSeries = $repository->countSeries();

            $nbSeriePerPage = ROUND($nbSeries/10); // combien de série par page 

            if($pageNumber == 1){
                $result = $repository->findSeriesFirstPage();
            }else{
                $result = $repository->findSeries($pageNumber);
            }
        } 
        return $this->render('series/index.html.twig', [
            'series' => $result,
            'form' => $formulaireRecherche->createView(),
            'numberPage' => $nbSeriePerPage,
        ]);
    }

     /**
     * @Route("poster/{id}", name="series_poster", methods={"GET","POST"})
     */
    public function poster(Series $series)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'image.jpeg');     
        $poster = $series->getPoster(); 
        $response->setContent(stream_get_contents($poster));
        return $response;
    }
    
    /**
     * @Route("/following", name="series_following", methods={"GET"})
     */
    public function following(): Response
    {
        return $this->render('series/follow.html.twig');
    }

    /**
     * @Route("/new", name="series_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $series = new Series();
        $form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($series);
            $entityManager->flush();

            return $this->redirectToRoute('series_index');
        }

        return $this->render('series/new.html.twig', [
            'series' => $series,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/show/{id}", name="series_show", methods={"GET", "POST"})
     */
    public function show(Series $series, Request $request): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $formRating = $this->createForm(RatingFormType::class);
        $formRating->handleRequest($request);
       
        if($formRating->isSubmitted()){
            foreach($series->getRatings() as $rate){
                if($rate->getUser() == $this->getUser()){
                    $entityManager->remove($rate);
                    $entityManager->flush();
                }
            }
            $rating = new Rating();
            $rating->setValue($formRating->get('notesArray')->getData());
            $rating->setDate(new \DateTime('now'));
            $rating->setComment($formRating->get('comment')->getData());   
            $entityManager->persist($rating);
            $this->getUser()->addRating($rating); 
            $entityManager->persist($this->getUser()); 
            $series->addRating($rating);
            $entityManager->persist($series);                         
            $entityManager->flush();
            return $this->redirectToRoute('series_show', ['id' => $series->getId()]);
        }     
        return $this->render('series/show.html.twig', [
            'series' => $series,
            'form_rating' => $formRating->createView(),
        ]);
    }

    /**
     * @Route("/show/{id}/follow", name="series_follow", methods={"GET"})
     */
    public function follow(Series $series): Response
    {
        if($series){
            $entityManager = $this->getDoctrine()->getManager();
            if($this->getUser()->getSeries()->contains($series)){
                $series->removeUser($this->getUser());
                $this->getUser()->removeSeries($series);     
            }else{
                $series->addUser($this->getUser());
                $this->getUser()->addSeries($series);        
            }   
            $entityManager->persist($series);
            $entityManager->persist($this->getUser());
    
            $entityManager->flush();
        }
        return $this->redirectToRoute('series_show', ['id' => $series->getId()]);
    }

    /**
     * @Route("/show/{id}/seen/{episode}", name="series_episode", methods={"GET"})
     */
    public function seen(Series $series, Episode $episode): Response
    {
        if($episode){
            $entityManager = $this->getDoctrine()->getManager();
            if($this->getUser()->getEpisode()->contains($episode)){
                $episode->removeUser($this->getUser());
                $this->getUser()->removeEpisode($episode);     
            }else{
                $episode->addUser($this->getUser());
                $this->getUser()->addEpisode($episode);        
            }   
            $entityManager->persist($episode);
            $entityManager->persist($this->getUser());
    
            $entityManager->flush();
        }
        return $this->redirectToRoute('series_show', ['id' => $series->getId()]);
    }

    /**
     * @Route("/admin/{id}/comment/{rate}/delete", name="series_rate_delete", methods={"GET"})
     */
    public function rate_delete(Series $series, Rating $rate): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($rate);
        $entityManager->flush();
        return $this->redirectToRoute('series_show', ['id' => $series->getId()]);  
    }


    /**
     * @Route("/{id}/edit", name="series_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Series $series): Response
    {
        $form = $this->createForm(SeriesType::class, $series);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('series_index');
        }

        return $this->render('series/edit.html.twig', [
            'series' => $series,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="series_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Series $series): Response
    {
        if ($this->isCsrfTokenValid('delete'.$series->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($series);
            $entityManager->flush();
        }

        return $this->redirectToRoute('series_index');
    }
}
