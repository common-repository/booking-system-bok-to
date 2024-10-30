let ul = document.getElementsByClassName("menu-item")[0].parentNode;
let new_li = document.createElement('li');

new_li.id = "menu-item";
new_li.className = "menu-item menu-item-type-custom";
ul.prepend(new_li);

let li = document.getElementById("menu-item");
let new_form = document.createElement('form');

new_form.id = "new_form_id"
new_form.method = "POST";
new_form.style = "text-align: center;";
li.append(new_form);

let form = document.getElementById("new_form_id");
let new_but = document.createElement('button');

new_but.id = "menu_view_button";
new_but.type = "submit";
new_but.name = "frame_menu_view";
new_but.style = "font-size: 18px;";
form.append(new_but);

let but = document.getElementById("menu_view_button");
but.append("MENU");