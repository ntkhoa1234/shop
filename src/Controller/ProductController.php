<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Doctrine\Common\Collections\Criteria;
/**
 * @Route("/product")
 */
class ProductController extends AbstractController
{
    /**
     * @Route("/list/{pageId}", name="app_product_index", methods={"GET"})
     */
    public function index(Request $request, ProductRepository $productRepository,
    CategoryRepository $categoryRepository,
    int $pageId = 1): Response
    {
        //$this->denyAccessUnlessGranted('ROLE_CUSTOMER');
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');
        $Cat = $request->query->get('category');
        $word = $request->query->get('name');
        $orderby = $request->query->get('orderBy');
        $sortBy = $request->query->get('sortBy');

        
        if(!(is_null($Cat)||empty($Cat))){
            $selectedCat=$Cat;
        }
        else
        $selectedCat='';


        $tempQuery = $productRepository->findMore($minPrice, $maxPrice, $Cat,$word,$sortBy,$orderby);
        $pageSize = 6;

    // load doctrine Paginator
        $paginator = new Paginator($tempQuery);

    // you can get total items
        $totalItems = count($paginator);

    // get total pages
        $numOfPages = ceil($totalItems / $pageSize);

    // now get one page's items:
        $tempQuery = $paginator
        ->getQuery()
        ->setFirstResult($pageSize * ($pageId - 1)) // set the offset
        ->setMaxResults($pageSize); // set the limit


        return $this->render('product/index.html.twig', [
            'products' =>  $tempQuery->getResult(),
            'selectedCat' => $selectedCat,
            'numOfPages' => $numOfPages
        ]);
    }



    /**
     * @Route("/new", name="app_product_new", methods={"GET", "POST"})
     */
    public function new(Request $request, ProductRepository $productRepository): Response
    {
        //$this->denyAccessUnlessGranted('ROLE_SELLER');
        //$hasAccess = $this->isGranted('ROLE_CUSTOMER');
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productRepository->add($product, true);

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }
        if ($hasAccess) {
        return $this->renderForm('product/new.html.twig', [
            'product' => $product,
                'form' => $form,
        ]);
        }
        if (!($hasAccess)) {
            return $this->redirectToRoute('app_login');
            }
        return $this->renderForm('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }
    /**
     * @Route("/plus", name="app_product_plus", methods={"GET"})
     */
    public function plus(Request $request): Response
    {
     $this->denyAccessUnlessGranted('ROLE_ADMIN');
     $firstNum = $request->query->get('a');
     $secondNum = $request->query->get('b');
     $Name = $request->query->get('name');
     return new Response(
        '<html><body>Tong: '.($firstNum + $secondNum).' welcome:'.($Name).'</body></html>'
     );

    }
    /**
     * @Route("/{id}", name="app_product_show", methods={"GET"})
     */
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_product_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Product $product, ProductRepository $productRepository): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $productRepository->add($product, true);

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }
    /**
 * @Route("/addCart/{id}", name="app_add_cart", methods={"GET"})
 */
     public function addCart(Product $product, Request $request)
    {
    $session = $request->getSession();
    $quantity = (int)$request->query->get('quantity');

    //check if cart is empty
    if (!$session->has('cartElements')) {
        //if it is empty, create an array of pairs (prod Id & quantity) to store first cart element.
        $cartElements = array($product->getId() => $quantity);
        //save the array to the session for the first time.
        $session->set('cartElements', $cartElements);
    } else {
        $cartElements = $session->get('cartElements');
        //Add new product after the first time. (would UPDATE new quantity for added product)
        $cartElements = array($product->getId() => $quantity) + $cartElements;
        //Re-save cart Elements back to session again (after update/append new product to shopping cart)
        $session->set('cartElements', $cartElements);
    }
    return new Response(); //means 200, successful
    }
/**
 * @Route("/reviewCart", name="app_review_cart", methods={"GET"})
 */
public function reviewCart(Request $request): Response
{
    $session = $request->getSession();
    if ($session->has('cartElements')) {
        $cartElements = $session->get('cartElements');
    } else
        $cartElements = [];
    return $this->json($cartElements);
}

    /**
 * @Route("/checkoutCart", name="app_checkout_cart", methods={"GET"})
 */
        public function checkoutCart(Request               $request,
                        OrderDetailRepository $orderDetailRepository,
                        OrderRepository       $orderRepository,
                        ProductRepository     $productRepository,
                        ManagerRegistry       $mr): Response
        {
                $this->denyAccessUnlessGranted('ROLE_USER');
                $entityManager = $mr->getManager();
                $session = $request->getSession(); //get a session
// check if session has elements in cart
                if ($session->has('cartElements') && !empty($session->get('cartElements'))) {
                    try {
// start transaction!
                $entityManager->getConnection()->beginTransaction();
                $cartElements = $session->get('cartElements');

//Create new Order and fill info for it. (Skip Total temporarily for now)
                $order = new Order();
                date_default_timezone_set('Asia/Ho_Chi_Minh');
                $order->setOrderDate(new \DateTime());
/** @var \App\Entity\User $user */
        $user = $this->getUser();
        $order->setUser($user);
        $orderRepository->add($order, true); //flush here first to have ID in Order in DB.

//Create all Order Details for the above Order
        $total = 0;
        foreach ($cartElements as $product_id => $quantity) {
        $product = $productRepository->find($product_id);
//create each Order Detail
        $orderDetail = new OrderDetail();
        $orderDetail->setOrd($order);
        $orderDetail->setProduct($product);
        $orderDetail->setQuantity($quantity);
        $orderDetailRepository->add($orderDetail);

        $total += $product->getPrice() * $quantity;
        }
        $order->setTotal($total);
        $orderRepository->add($order);
// flush all new changes (all order details and update order's total) to DB
        $entityManager->flush();

// Commit all changes if all changes are OK
        $entityManager->getConnection()->commit();

// Clean up/Empty the cart data (in session) after all.
        $session->remove('cartElements');
        } catch (Exception $e) {
// If any change above got trouble, we roll back (undo) all changes made above!
        $entityManager->getConnection()->rollBack();
        }
              return new Response("Check in DB to see if the checkout process is successful");
            } else
              return new Response("Nothing in cart to checkout!");
        }

    /**
     * @Route("/{id}", name="app_product_delete", methods={"POST"})
     */
    public function delete(Request $request, Product $product, ProductRepository $productRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $productRepository->remove($product, true);
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
    /**
 * @Route("/setRole", name="app_set_role", methods={"GET"})
  */
 public function setRole(UserRepository $userRepository): JsonResponse
 {
    /** @var \App\Entity\User $user */
    $this->denyAccessUnlessGranted('ROLE_ADMIN');
     return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    
 }



}

