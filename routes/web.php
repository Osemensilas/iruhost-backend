<?php

use App\Controllers\API\AuthController;
use App\Controllers\API\LogoutController;
use App\Controllers\API\SessionController;
use App\Controllers\API\DomainRegistration;
use App\Controllers\API\AddToCart;
use App\Controllers\API\CartItems;
use App\Controllers\API\CallFlutter;
use App\Controllers\API\ChatsController;
use App\Controllers\API\UserProducts;


$router->get('/api/session', [SessionController::class, 'userSession']);
$router->get('/api/session-data', [SessionController::class, 'userData']);
$router->post('/api/register', [AuthController::class, 'register']);
$router->post('/api/login', [AuthController::class, 'login']);
$router->post('/api/admin-login', [AuthController::class, 'adminLogin']);
$router->post('/api/update-naira', [AuthController::class, 'currency']);
$router->get('/api/get-naira', [AuthController::class, 'getNaira']);
$router->post('/api/logout', [LogoutController::class, 'userLogout']);
$router->post('/api/domain-search', [DomainRegistration::class, 'domainSearch']);
$router->post('/api/single-search', [DomainRegistration::class, 'singleSearch']);
$router->post('/api/domain-check', [DomainRegistration::class, 'existingCheck']);
$router->post('/api/add-to-cart', [AddToCart::class, 'addDomain']);
$router->post('/api/add-to-cart-hosting', [AddToCart::class, 'addHosting']);
$router->post('/api/add-to-cart-ssl', [AddToCart::class, 'addSSL']);
$router->get('/api/cart-items', [CartItems::class, 'cartItems']);
$router->get('/api/cart-total-price', [CartItems::class, 'cartTotal']);
$router->get('/api/cart-domain', [CartItems::class, 'cartDomain']);
$router->post('/api/empty-user-cart', [CartItems::class, 'clearAllItems']);
$router->post('/api/remove-item', [CartItems::class, 'removeItem']);
$router->get('/api/cart-session', [CartItems::class, 'cartSession']);
$router->post('/api/payment-success', [CallFlutter::class, 'paymentSuccessful']);
$router->post('/api/chat-registration', [ChatsController::class, 'createChatUser']);
$router->post('/api/add-chat', [ChatsController::class, 'addChat']);
$router->get('/api/get-chats', [ChatsController::class, 'getChats']);
$router->get('/api/get-dashboard', [UserProducts::class, 'getDashboardProducts']);
