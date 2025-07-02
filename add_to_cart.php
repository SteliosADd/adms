
<?php
include('include/connect.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page with return URL
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $userId = $_SESSION['user_id'];

    switch ($action) {
        case 'add':
            $productId = (int)$_POST['product_id'];
            if ($productId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
                exit();
            }
            $insertQuery = "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("ii", $userId, $productId);
            $stmt->execute();
            $stmt->close();
            break;

        case 'remove':
            $productId = (int)$_POST['product_id'];
            if ($productId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
                exit();
            }
            $deleteQuery = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
            $stmt = $conn->prepare($deleteQuery);
            $stmt->bind_param("ii", $userId, $productId);
            $stmt->execute();
            $stmt->close();
            break;

        case 'get':
            $wishlistQuery = "SELECT wishlist.*, products.name, products.price FROM wishlist 
                              JOIN products ON wishlist.product_id = products.id 
                              WHERE wishlist.user_id = ?";
            $stmt = $conn->prepare($wishlistQuery);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $wishlistResult = $stmt->get_result();
            $wishlistItems = [];
            while ($row = $wishlistResult->fetch_assoc()) {
                $wishlistItems[] = $row;
            }
            $stmt->close();
            echo json_encode($wishlistItems);
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Document</title>
    
</head>
<body class="">
    
    <div class="container">
        <header>
            <div class="title">PRODUCT LIST</div>
            <div class="icon-cart">
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 15a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm0 0h8m-8 0-1-4m9 4a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-9-4h10l2-7H3m2 7L3 4m0 0-.792-3H1"/>
                </svg>
                <span>0</span>
            </div>
        </header>
        <div class="product-list">
    <div class="conatainer"></div>
    <div class="Product" id="Product">
        <img src="./img/air.png" alt="" class="ProductImg">
       
        <div class="ProductDetails">
            <h1 class="productTitle">AIR FORCE</h1>
            <h2 class="productPrice">$100</h2>
            <p class="productDesc">Lorem ipsum dolor sit amet consectetur impal adipisicing elit. Alias assumenda
                dolorum
                doloremque sapiente aliquid aperiam.</p>
            <div class="colors">
                <div class="color"></div>
                <div class="color"></div>
            </div>
      
<div class="product-controls">
    <button class="decrease" onclick="updateQuantity(-1)">-</button>
    <span class="quantity">1</span>
    <button class="increase" onclick="updateQuantity(1)">+</button>
</div>
            <form method='post' action='cart_action.php'>
                    <input type='hidden' name='wishlist_id' value='{$row['id']}'>
                    <input type='hidden' name='action' value='move_to_cart'>
                    <button type='submit'>Move to Cart</button>
                    <button onclick="removeFromWishlist(<?= $row['product_id'] ?>)">Remove</button>
                </form>
    </div>
   
    </div>
    <div class="cartTab">
        <h1>Shopping Cart</h1>
        <div class="listCart">
            
        </div>
        <div class="btn">
            <button class="close">CLOSE</button>
            <button class="checkOut">Check Out</button>
        </div>
    </div>

</body>
</html>
<style>
body{
    margin: 0;
    font-family: Poppins;
}
.container{
    width: 900px;
    margin: auto;
    max-width: 90vw;
    text-align: center;
    padding-top: 10px;
    transition: transform .5s;
}
svg{
    width: 30px;
}
header{
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 0;
}
.icon-cart{
    position: relative;
}
.icon-cart span{
    position: absolute;
    background-color: red;
    width: 30px;
    height: 30px;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 50%;
    color: #fff;
    top: 50%;
    right: -20px;
}
.title{
    font-size: xx-large;
}
.listProduct .item img{
    width: 90%;
    filter: drop-shadow(0 50px 20px #0009);
}
.listProduct{
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}
.listProduct .item{
    background-color: #EEEEE6;
    padding: 20px;
    border-radius: 20px;
}
.listProduct .item h2{
    font-weight: 500;
    font-size: large;
}
.listProduct .item .price{
    letter-spacing: 7px;
    font-size: small;
}
.listProduct .item button{
    background-color: #353432;
    color: #eee;
    border: none;
    padding: 5px 10px;
    margin-top: 10px;
    border-radius: 20px;
}

/* cart */
.cartTab{
    width: 400px;
    background-color: #353432;
    color: #eee;
    position: fixed;
    top: 0;
    right: -400px;
    bottom: 0;
    display: grid;
    grid-template-rows: 70px 1fr 70px;
    transition: .5s;
}
body.showCart .cartTab{
    right: 0;
}
body.showCart .container{
    transform: translateX(-250px);
}
.cartTab h1{
    padding: 20px;
    margin: 0;
    font-weight: 300;
}
.cartTab .btn{
    display: grid;
    grid-template-columns: repeat(2, 1fr);
}
.cartTab button{
    background-color: #E8BC0E;
    border: none;
    font-family: Poppins;
    font-weight: 500;
    cursor: pointer;
}
.cartTab .close{
    background-color: #eee;
}
.listCart .item img{
    width: 100%;
}
.listCart .item{
    display: grid;
    grid-template-columns: 70px 150px 50px 1fr;
    gap: 10px;
    text-align: center;
    align-items: center;
}
.listCart .quantity span{
    display: inline-block;
    width: 25px;
    height: 25px;
    background-color: #eee;
    border-radius: 50%;
    color: #555;
    cursor: pointer;
}
.listCart .quantity span:nth-child(2){
    background-color: transparent;
    color: #eee;
    cursor: auto;
}
.listCart .item:nth-child(even){
    background-color: #eee1;
}
.listCart{
    overflow: auto;
}
.listCart::-webkit-scrollbar{
    width: 0;
}
@media only screen and (max-width: 992px) {
    .listProduct{
        grid-template-columns: repeat(3, 1fr);
    }
}


/* mobile */
@media only screen and (max-width: 768px) {
    .listProduct{
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>

let listProductHTML = document.querySelector('.listProduct');
let listCartHTML = document.querySelector('.listCart');
let iconCart = document.querySelector('.icon-cart');
let iconCartSpan = document.querySelector('.icon-cart span');
let body = document.querySelector('body');
let closeCart = document.querySelector('.close');
let products = [];
let cart = [];


iconCart.addEventListener('click', () => {
    body.classList.toggle('showCart');
})
closeCart.addEventListener('click', () => {
    body.classList.toggle('showCart');
})

    const addDataToHTML = () => {
    // remove datas default from HTML

        // add new datas
        if(products.length > 0) // if has data
        {
            products.forEach(product => {
                let newProduct = document.createElement('div');
                newProduct.dataset.id = product.id;
                newProduct.classList.add('item');
                newProduct.innerHTML = 
                `<img src="${product.image}" alt="">
                <h2>${product.name}</h2>
                <div class="price">$${product.price}</div>
                <button class="addCart">Add To Cart</button>`;
                listProductHTML.appendChild(newProduct);
            });
        }
    }
    listProductHTML.addEventListener('click', (event) => {
        let positionClick = event.target;
        if(positionClick.classList.contains('addCart')){
            let id_product = positionClick.parentElement.dataset.id;
            addToCart(id_product);
        }
    })
const addToCart = (product_id) => {
    let positionThisProductInCart = cart.findIndex((value) => value.product_id == product_id);
    if(cart.length <= 0){
        cart = [{
            product_id: product_id,
            quantity: 1
        }];
    }else if(positionThisProductInCart < 0){
        cart.push({
            product_id: product_id,
            quantity: 1
        });
    }else{
        cart[positionThisProductInCart].quantity = cart[positionThisProductInCart].quantity + 1;
    }
    addCartToHTML();
    addCartToMemory();
}
const addCartToMemory = () => {
    localStorage.setItem('cart', JSON.stringify(cart));
}
const addCartToHTML = () => {
    listCartHTML.innerHTML = '';
    let totalQuantity = 0;
    if(cart.length > 0){
        cart.forEach(item => {
            totalQuantity = totalQuantity +  item.quantity;
            let newItem = document.createElement('div');
            newItem.classList.add('item');
            newItem.dataset.id = item.product_id;

            let positionProduct = products.findIndex((value) => value.id == item.product_id);
            let info = products[positionProduct];
            listCartHTML.appendChild(newItem);
            newItem.innerHTML = `
            <div class="image">
                    <img src="${info.image}">
                </div>
                <div class="name">
                ${info.name}
                </div>
                <div class="totalPrice">$${info.price * item.quantity}</div>
                <div class="quantity">
                    <span class="minus"><</span>
                    <span>${item.quantity}</span>
                    <span class="plus">></span>
                </div>
            `;
        })
    }
    iconCartSpan.innerText = totalQuantity;
}

listCartHTML.addEventListener('click', (event) => {
    let positionClick = event.target;
    if(positionClick.classList.contains('minus') || positionClick.classList.contains('plus')){
        let product_id = positionClick.parentElement.parentElement.dataset.id;
        let type = 'minus';
        if(positionClick.classList.contains('plus')){
            type = 'plus';
        }
        changeQuantityCart(product_id, type);
    }
})
const changeQuantityCart = (product_id, type) => {
    let positionItemInCart = cart.findIndex((value) => value.product_id == product_id);
    if(positionItemInCart >= 0){
        let info = cart[positionItemInCart];
        switch (type) {
            case 'plus':
                cart[positionItemInCart].quantity = cart[positionItemInCart].quantity + 1;
                break;
        
            default:
                let changeQuantity = cart[positionItemInCart].quantity - 1;
                if (changeQuantity > 0) {
                    cart[positionItemInCart].quantity = changeQuantity;
                }else{
                    cart.splice(positionItemInCart, 1);
                }
                break;
        }
    }
    addCartToHTML();
    addCartToMemory();
}

const initApp = () => {
    // get data product
    fetch('products.json')
    .then(response => response.json())
    .then(data => {
        products = data;
        addDataToHTML();

        // get data cart from memory
        if(localStorage.getItem('cart')){
            cart = JSON.parse(localStorage.getItem('cart'));
            addCartToHTML();
        }
    })
}
initApp();

[
    {
        "id": 1,
        "name":" LD01 LOUNGE CHAIR",
        "price": 200,
        "image": "image/1.png"
    },
    {
        "id": 2,
        "name":" LD02 LOUNGE CHAIR",
        "price": 250,
        "image": "image/2.png"
    },
    {
        "id": 3,
        "name":" LD03 LOUNGE CHAIR",
        "price": 290,
        "image": "image/3.png"
    },
    {
        "id": 4,
        "name":" LD04 LOUNGE CHAIR",
        "price": 200,
        "image": "image/4.png"
    },
    {
        "id": 5,
        "name":" LD05 LOUNGE CHAIR",
        "price": 300,
        "image": "image/5.png"
    },
    {
        "id": 6,
        "name":" LD06 LOUNGE CHAIR",
        "price": 200,
        "image": "image/6.png"
    },
    {
        "id": 7,
        "name":" LD07 LOUNGE CHAIR",
        "price": 200,
        "image": "image/7.png"
    },
    {
        "id": 8,
        "name":" LD08 LOUNGE CHAIR",
        "price": 200,
        "image": "image/8.png"
    }

]
</script>