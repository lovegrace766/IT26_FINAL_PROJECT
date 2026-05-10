const foodItems = [

    {
        id:1,
        name:'Cheese Burger',
        price:12,
        rating:4.8,
        img:'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=200'
    },

    {
        id:2,
        name:'Pepperoni Pizza',
        price:18,
        rating:4.9,
        img:'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=200'
    },

    {
        id:3,
        name:'Pasta',
        price:14,
        rating:4.7,
        img:'https://images.unsplash.com/photo-1563379926898-05f4575a45d8?w=200'
    }

];

let cart = [];
let favorites = [];

function renderFoodGrid(filter=''){

    const grid = document.getElementById('food-grid');

    grid.innerHTML = '';

    const filtered = foodItems.filter(item =>
        item.name.toLowerCase().includes(filter.toLowerCase())
    );

    filtered.forEach(item => {

        const isFav = favorites.includes(item.id);

        grid.innerHTML += `
        
            <div class="card">

                <button class="fav-btn ${isFav ? 'active':''}"
                    onclick="toggleFavorite(${item.id})">

                    <i class='bx ${isFav ? 'bxs-heart':'bx-heart'}'></i>

                </button>

                <img src="${item.img}">

                <h3>${item.name}</h3>

                <div class="rating">
                    ⭐ ${item.rating}
                </div>

                <div class="price-row">

                    <span class="price">
                        $${item.price}
                    </span>

                    <button class="add-btn"
                        onclick="addToCart(${item.id})">
                        +
                    </button>

                </div>

            </div>
        `;
    });
}

function toggleFavorite(id){

    if(favorites.includes(id)){

        favorites = favorites.filter(f => f !== id);

    } else {

        favorites.push(id);
    }

    renderFoodGrid();
    renderFavorites();
}

function renderFavorites(){

    const grid = document.getElementById('favorites-grid');

    grid.innerHTML = '';

    const favItems = foodItems.filter(item =>
        favorites.includes(item.id)
    );

    if(favItems.length === 0){

        document.getElementById('no-favorites').style.display='block';

    } else {

        document.getElementById('no-favorites').style.display='none';

        favItems.forEach(item => {

            grid.innerHTML += `
            
                <div class="card">

                    <button class="fav-btn active"
                        onclick="toggleFavorite(${item.id})">

                        <i class='bx bxs-heart'></i>

                    </button>

                    <img src="${item.img}">

                    <h3>${item.name}</h3>

                    <div class="rating">
                        ⭐ ${item.rating}
                    </div>

                    <div class="price-row">

                        <span class="price">
                            $${item.price}
                        </span>

                        <button class="add-btn"
                            onclick="addToCart(${item.id})">
                            +
                        </button>

                    </div>

                </div>
            `;
        });
    }
}

function addToCart(id){

    const item = foodItems.find(food => food.id === id);

    const existing = cart.find(c => c.id === id);

    if(existing){

        existing.quantity++;

    } else {

        cart.push({...item, quantity:1});
    }

    updateCartUI();
}

function removeFromCart(id){

    cart = cart.filter(item => item.id !== id);

    updateCartUI();
}

function updateCartUI(){

    const container = document.getElementById('cart-items');

    container.innerHTML='';

    let subtotal = 0;

    cart.forEach(item => {

        subtotal += item.price * item.quantity;

        container.innerHTML += `
        
            <div class="order-item">

                <img src="${item.img}">

                <div class="order-details">

                    <h4>${item.name}</h4>

                    <p>x${item.quantity}</p>

                </div>

                <span class="order-price">
                    $${item.price * item.quantity}
                </span>

                <button class="remove-btn"
                    onclick="removeFromCart(${item.id})">

                    ×

                </button>

            </div>
        `;
    });

    const delivery = subtotal > 50 ? 0 : 5;

    const total = subtotal + delivery;

    document.getElementById('cart-subtotal').innerText =
        `$${subtotal.toFixed(2)}`;

    document.getElementById('cart-delivery').innerText =
        subtotal > 50 ? 'FREE' : `$${delivery.toFixed(2)}`;

    document.getElementById('cart-total').innerText =
        `$${total.toFixed(2)}`;

    renderCheckoutSummary();
}

function renderCheckoutSummary(){

    const list = document.getElementById('checkout-items-list');

    list.innerHTML='';

    let subtotal = 0;

    cart.forEach(item => {

        subtotal += item.price * item.quantity;

        list.innerHTML += `
        
            <div class="summary-item">

                <span>${item.name} x${item.quantity}</span>

                <span>$${item.price * item.quantity}</span>

            </div>
        `;
    });

    const delivery = subtotal > 50 ? 0 : 5;

    const total = subtotal + delivery;

    document.getElementById('chk-subtotal').innerText =
        `$${subtotal.toFixed(2)}`;

    document.getElementById('chk-delivery').innerText =
        subtotal > 50 ? 'FREE' : `$${delivery.toFixed(2)}`;

    document.getElementById('chk-total').innerText =
        `$${total.toFixed(2)}`;
}

function placeOrder(){

    if(cart.length > 0){

        alert('Order placed successfully!');

        cart=[];

        updateCartUI();

    } else {

        alert('Cart is empty!');
    }
}

function sendMessage(){

    const input = document.getElementById('message-input');

    const box = document.querySelector('.chat-messages');

    if(input.value.trim()==='') return;

    box.innerHTML += `
    
        <div class="message sent">
            ${input.value}
        </div>
    `;

    input.value='';

    setTimeout(() => {

        box.innerHTML += `
        
            <div class="message received">
                Thanks for your message!
            </div>
        `;

    },1000);
}

function switchView(view){

    document.querySelectorAll('.view-section')
        .forEach(v => v.classList.remove('active-view'));

    document.getElementById(`view-${view}`)
        .classList.add('active-view');

    document.querySelectorAll('.nav-links a')
        .forEach(link => link.classList.remove('active'));

    document.querySelector(`[data-view="${view}"]`)
        .classList.add('active');
}

document.querySelectorAll('.nav-links a')
.forEach(link => {

    link.addEventListener('click', e => {

        e.preventDefault();

        switchView(link.dataset.view);
    });
});

document.getElementById('search-input')
.addEventListener('input', e => {

    renderFoodGrid(e.target.value);
});

document.getElementById('checkout-btn')
.addEventListener('click', () => {

    switchView('checkout');
});

document.getElementById('clear-cart')
.addEventListener('click', () => {

    cart=[];

    updateCartUI();
});

renderFoodGrid();
renderFavorites();
updateCartUI();