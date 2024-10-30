function view_price(data) {
let div = document.getElementById('bokto-service-price');
let input_price = document.getElementById('service_price');
    if (data === "paid") {
        div.removeAttribute('class');
        input_price.setAttribute('required', 'true');
    } else if (data === "free") {
        div.setAttribute("class", "hidden");
        input_price.removeAttribute('required');
    }
}