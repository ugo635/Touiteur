function afficherPlus(post) {
    let message = document.querySelector(`#${post} .msg-content`)
    message.classList.remove("line-clamp-2")

    let btn = document.querySelector(`#${post} .afficher-plus`)
    btn.remove()
}

async function like(post, user, change = 1) {
    // TODO: Faire l'action pr like
    /*
    const res = await fetch(`?action=like&post=${post}`, {
        method: "POST",
        body: JSON.stringify({
            user: user
        })
    });
     */
}

