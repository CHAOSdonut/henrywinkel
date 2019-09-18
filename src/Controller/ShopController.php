<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Product;
use App\Entity\Order;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

class ShopController extends AbstractController
{
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }


    /**
     * @Route("/shop", name="shop")
     */
    public function index()
    {
        $product = $this->getDoctrine()
            ->getRepository(Product::class)
            ->findAll();

        return $this->render('shop/shop.html.twig', [
            'products' => $product,
        ]);
    }

    /**
     * @Route("/shop/addtocart/{id}", name="addtocart")
     */
    public function addtocart(Product $product, Request $request)
    {
        $cart = $this->session->get('cart');
        if(isset($cart[$product->getId()])) {
            $cart[$product->getId()]['aantal']++;
        }
        else{
            $cart[$product->getId()] = array('aantal' => 1);
        }

        $this->session->set('cart', $cart);

        return $this->forward('App\Controller\ShopController::index');
    }

    /**
     * @Route("/shop/cart", name="cart")
     */
    public function cart()
    {
        $cart = $this->session->get('cart');
        $cartArray = [];
        $total = 0;
        foreach($cart as $id => $product){

            $res = $this->getDoctrine()
                ->getRepository(Product::class)
                ->find($id);

            array_push($cartArray, [$id, $product['aantal'], $res]);
            $total = $total + ((($res->getPrice()/100) * (100 + $res->getTaxcode()))  * $product['aantal']);
        }

        return $this->render('shop/cart.html.twig', ['cart' =>  $cartArray, 'totaal' => $total]);
    }

    /**
     * @Route("/shop/placeorder", name="placeorder")
     */
    public function placeorder()
    {
        if($user = $this->getUser()){

            $entityManager = $this->getDoctrine()->getManager();

            $order = new Order();
            $time = new \DateTime();
            $time->format('H:i:s \O\n Y-m-d');
            $order->setUserId($user);
            $order->setDate($time);
            $order->setPaid(false);

            // tell Doctrine you want to (eventually) save the Product (no queries yet)
            $entityManager->persist($order);

            // actually executes the queries (i.e. the INSERT query)
            $entityManager->flush();

            return $this->forward('App\Controller\ShopController::index');
        }
        else{
            return $this->redirect('../login');
        }
    }

}
