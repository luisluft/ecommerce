<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use Hcode\Model\OrderStatus;

$app->get(
    '/admin/orders/:idorder/delete',
    function ($idorder) {
        User::verifyLogin();
        
        $order = new Order();
        
        $order->get((int)$idorder);

        $order->delete();

        header('Location: /admin/orders');
        exit;
    }
);

$app->get(
    '/admin/orders/:idorder/status',
    function ($idorder) {
        User::verifyLogin();
        
        $order = new Order();
        
        $order->get((int)$idorder);

        $page = new PageAdmin();

        $page->setTpl(
            "order-status",
            [
                'order'=>$order->getValues(),
                'status'=>OrderStatus::listAll(),
                'msgSuccess'=>Order::getSuccess(),
                'msgError'=>Order::getError()
            ]
        );
    }
);

$app->post(
    '/admin/orders/:idorder/status',
    function ($idorder) {
        User::verifyLogin();
        
        $order = new Order();
        
        $order->get((int)$idorder);

        // Not able to change order status
        if (!isset($_POST['idstatus']) || !(int)$_POST['idstatus'] > 0) {
            Order::msgError("Informe o status atual do pedido.");
            header('Location: /admin/orders/' . $idorder . '/status');
            exit;
        } else { // Able to change order's status

            $order->setidstatus((int)$_POST['idstatus']);
            $order->save();
            Order::setSuccess("Status Atualizado");
            header('Location: /admin/orders/' . $idorder . '/status');
            exit;
        }

        
        $page = new PageAdmin();

        $page->setTpl(
            "order-status",
            [
                'order'=>$order->getValues(),
                'status'=>OrderStatus::listAll(),
                'msgSuccess'=>Order::getSuccess(),
                'msgError'=>Order::getError()
            ]
        );
    }
);

// Details page for orders in admin side
$app->get(
    '/admin/orders/:idorder',
    function ($idorder) {
        User::verifyLogin();
        
        $order = new Order();
        
        $order->get((int)$idorder);

        $cart = $order->getCart();

        $page = new PageAdmin();

        $page->setTpl(
            "order",
            [
                'order'=>$order->getValues(),
                'cart'=>$cart->getValues(),
                'products'=>$cart->getProducts()
            ]
        );
    }
);

// Route direction for the orders admin page
$app->get(
    '/admin/orders',
    function () {
        User::verifyLogin();
        
        $page = new PageAdmin();
        
        $page->setTpl(
            "orders",
            [
                'orders'=>Order::listAll()
            ]
        );
    }
);
