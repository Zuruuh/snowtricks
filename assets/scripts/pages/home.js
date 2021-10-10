const container = document.querySelector("#tricks-preview");
const loggedIn = !!attribute("data-logged-in");
const url = attribute("data-home-url");
const endpoint = "api/tricks/";
const loadMoreBtn = document.querySelector("#load-more-button");

let total = 0;
let index = 0;
let stopped = false;

loadMoreBtn.addEventListener("click",async (e) => {
    loadMoreBtn.disabled = true;
    loadMoreBtn.classList.add("disabled");
    if (stopped) {
        loadMoreBtn.innerHTML = `No more tricks to load 🙁`;
        return;
    }
    loadMoreBtn.innerHTML = `Loading... <i class="fas fa-spinner fa-spin"></i>`;

    await fetchTricks().catch(err => console.error(err.message)) ;

    loadMoreBtn.disabled = false;
    loadMoreBtn.classList.remove("disabled");
    loadMoreBtn.innerHTML = `Load More <i class="fas fa-plus-square"></i>`;
})

function attribute(string) {
    const attr = container.getAttribute(string);
    container.removeAttribute(string);
    return attr;
}

async function fetchTricks(qty = 3) {
    const data = await fetch(`${url}${endpoint}?max=${qty}&index=${index}&total=${total}`)
    const json = await data.json();
    total = total === 0 ? json.total : total;
    index = json.index;
    json.tricks.map(trick => displayTrick(trick));
    stopped = total < 0 || index === total;
}

function displayTrick(trick) {
   const div = createElement("div", "col-sm-6 col-md-4 col-xl-3 g-2");
    const actions = `
    <div class="trick-card-actions">
        <button class="trick-card-edit link link-primary border-0">
            <a href="${url}tricks/edit/${trick.t_slug}">
                <i class="fas fa-pen"></i>
            </a>
        </button>
        <button data-bs-toggle="modal" data-bs-target="#page-modal" class="trick-card-delete link link-danger border-0">
            <i class="fas fa-trash-alt"></i>
        </button>
    </div>`;

    div.innerHTML = `
    <div class="card trick-card h-100">
        <img src="${trick.t_thumbnail}" class="img-responsive"  />
        <div class="trick-card-infos">
            <a class="trick-card-title" href="${url}tricks/details/${trick.t_slug}">${trick.t_name}</a>
            ${loggedIn ? actions : ""}
        </div>
    </div>`;
    container.appendChild(div);

    div.querySelector("button").addEventListener("click", () => {
        // TODO fix this 
        // const modal = document.querySelector("#page-modal");
        // const title = modal.querySelector(".modal-title")
        // const deleteBtn = modal.querySelector("button.btn-danger")

        title.innerText = `Trick: ${trick.t_name}`;
        deleteBtn.parentNode.href = `${url}tricks/delete/${trick.t_slug}`;     
    })

    return div;
}

function createElement(tag, classes = [], attributes = {}) {
    const element = document.createElement(tag);
    
    classes.split(" ").forEach(className => {
        element.classList.add(className)
    });
    for (const attribute in attributes) {
        element.setAttribute(attribute, attributes[attribute]);
    }

    return element;
}

fetchTricks(6);