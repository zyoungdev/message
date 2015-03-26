var APP = (function()
{
    var
    hf = (function()
    {
        return{
            elCN: function(cn)
            {
                return document.getElementsByClassName(cn);
            },
            isInside: function(node, target)
            {
                for(; node != null; node = node.parentNode)
                {
                    if (node == target) return true;
                }
            },
            ajax: function(type, fd, uri, callback)
            {
                var
                xhr = new XMLHttpRequest();

                xhr.onload = function()
                {
                    callback(this.response);
                }
                xhr.open(type, uri, true);
                if (fd) xhr.send(fd);
                else xhr.send();
            }
        };
    })(),
    sendMessage = (function()
    {
        var
        sendPlaintext = function(rec, pt)
        {
            if (pt === "") return;
            var
            fd = new FormData();

            fd.append("plaintext", pt);
            fd.append("recipient", rec);

            hf.ajax("POST", fd, "phpSrc/sendMessage.php", function(res)
            {
                console.log(res);
                listContacts.init();
            });
        }
        return{
            init: function()
            {

                hf.ajax("GET", null, "templates/sendMessage.php", function(res)
                {
                    var
                    div = document.createElement("div");
                    div.className = "module-container";
                    div.innerHTML = res;
                    document.body.appendChild(div);
                });
            },
            click: function(ev)
            {
                var
                e = ev.target,
                sendMessageBox = hf.elCN("send-message-box")[0];

                if (hf.isInside(e, sendMessageBox))
                {
                    var
                    rec = sendMessageBox.getElementsByTagName("input")[0],
                    ta = sendMessageBox.getElementsByTagName("textarea")[0],
                    sub = sendMessageBox.getElementsByTagName("button")[0];
                    if (e == ta)
                    {

                    }
                    else if (e == sub)
                    {
                        sendPlaintext(rec.value, ta.value);
                    }
                }
            }
        };
    })(),
    listContacts = (function()
    {
        var
        contactList,
        buildList = function()
        {
            var
            contactListContainer = hf.elCN('contact-list-container')[0],
            addContactInput = document.createElement("input"),
            addContactButton = document.createElement("button");

            addContactInput.className = "add-contact-input";
            addContactButton.className = "add-contact-button";

            contactListContainer.innerHTML = "";
            contactListContainer.appendChild(addContactInput);
            contactListContainer.appendChild(addContactButton);

            if (contactList["code"] == 0) return;

            for (var user in contactList)
            {
                var
                contact = document.createElement("div"),
                userDiv = document.createElement("div"),
                deleteButton = document.createElement("div");

                deleteButton.innerText = "Delete";
                deleteButton.className = "delete-contact-in-list";
                contact.className = "contact";
                userDiv.innerText = user;

                contact.appendChild(userDiv);
                contact.appendChild(deleteButton);
                contactListContainer.appendChild(contact);
            }
        },
        getList = function()
        {
            hf.ajax("GET", null, "phpSrc/listContacts.php", function(res)
            {
                // console.log(res);
                var
                div = document.createElement("div");
                div.className = "module-container contact-list-container",
                elemExists = document.getElementsByClassName("contact-list-container")[0];

                if (!elemExists) document.body.appendChild(div);

                contactList = JSON.parse(res);
                console.log(contactList);
                buildList();
            });
        },
        addContact = function(u)
        {
            console.log("yay");
            var
            fd = new FormData();

            fd.append("contact", u);

            hf.ajax("POST", fd, "phpSrc/addContact.php", function(res)
            {
                // console.log(res);
                hf.ajax("GET", null, "phpSrc/listContacts.php", function(r)
                {
                    contactList = JSON.parse(r);
                    if (contactList["code"] == null)
                    {
                        console.log(contactList);
                        buildList();
                    }
                });
            });
        },
        deleteContact = function(i, u)
        {
            var
            fd = new FormData();

            fd.append("username", u);

            hf.ajax("POST", fd, "phpSrc/deleteContact.php", function(res)
            {
                console.log(res);
                // res = JSON.parse(res);
                if (res["code"] == null)
                {
                    delete contactList[u];
                    buildList();
                }
                else
                {
                    console.log(res);
                }
            });
        };
        return {
            init: function()
            {
                getList();

            },
            click: function(ev)
            {
                var
                e = ev.target,
                contactListContainer = hf.elCN("contact-list-container")[0];

                if (hf.isInside(e, contactListContainer))
                {
                    if (ev.target.parentNode.className == "contact")
                    {
                        var index = Array.prototype.indexOf.call(contactListContainer.children, ev.target.parentNode);
                        var user = ev.target.parentNode.children[0].innerText;
                        if (ev.target.className == "delete-contact-in-list")
                        {
                            deleteContact(index, user);
                        }
                        else
                        {
                            // viewMessage(user, time);
                        }
                    }
                    if (ev.target.className == "add-contact-button")
                    {
                        var
                        user = hf.elCN("add-contact-input")[0].value;
                        addContact(user)
                    }
                }
            }
        };
    })(),
    listMessages = (function()
    {
        var
        messageList,
        buildList = function()
        {
            var
            listContainer = hf.elCN("message-list-container")[0];
            listContainer.innerHTML = "";

            for (var user in messageList)
            {
                var
                userFrag = document.createDocumentFragment();
                
                

                for (var message in messageList[user])
                {
                    var
                    messageDiv = document.createElement("div"),
                    username = document.createElement("div"),
                    time = document.createElement("div"),
                    closeButton = document.createElement("div");

                    closeButton.innerText = "Delete";
                    closeButton.className = "delete-message-in-list";
                    messageDiv.className = "message-in-list";
                    username.innerText = user;

                    time.innerText = messageList[user][message].timestamp;

                    messageDiv.appendChild(username);
                    messageDiv.appendChild(time);
                    messageDiv.appendChild(closeButton);
                    listContainer.appendChild(messageDiv);
                }
            }
        },
        viewMessage = function(u,t)
        {
            var
            fd = new FormData();

            fd.append("username", u);
            fd.append("timestamp", t);

            hf.ajax("POST", fd, "phpSrc/viewMessage.php", function(res)
            {
                console.log(res);
            });
        },
        deleteMessage = function(i, u, t)
        {
            var
            fd = new FormData();

            fd.append("timestamp", t);
            fd.append("username", u);

            hf.ajax("POST", fd, "phpSrc/deleteMessage.php", function(res)
            {
                console.log(res);
                // res = JSON.parse(res);
                if (res["code"] == null)
                {
                    delete messageList[u][t];
                    buildList();
                }
                else
                {
                    console.log(res);
                }
            });
        },
        getList = function()
        {
            hf.ajax("GET", null, "phpSrc/listMessages.php", function(res)
            {
                if (document.getElementsByClassName("message-list-container")[0])
                {
                    document.getElementsByClassName("message-list-container")[0].parentNode.removeChild(document.getElementsByClassName("message-list-container")[0]);
                }

                var
                div = document.createElement("div");
                div.className = "module-container message-list-container";
                document.body.appendChild(div);

                messageList = JSON.parse(res);
                console.log(messageList);
                if (messageList["code"] == null)
                {
                    buildList();
                }
            });
        };

        return{
            init: function()
            {
                getList();
            },
            click: function(ev)
            {
                var
                e = ev.target,
                messageListContainer = hf.elCN("message-list-container")[0];

                if (hf.isInside(e, messageListContainer))
                {
                    if (ev.target.parentNode.className == "message-in-list")
                    {
                        var index = Array.prototype.indexOf.call(messageListContainer.children, ev.target.parentNode);
                        var
                        user = ev.target.parentNode.children[0].innerText,
                        time = ev.target.parentNode.children[1].innerText;

                        if (ev.target.className == "delete-message-in-list")
                        {
                            deleteMessage(index, user, time);
                        }
                        else
                        {
                            // viewMessage(user, time);
                        }
                    }
                }
            }
        }
    })(),
    login = (function()
    {
        var
        submitCreds = function(un, pw)
        {
            if (un === "") return;
            if (pw === "") return;

            //send creds to phpSrc/login.php
            var
            fd = new FormData();

            fd.append("username", un);
            fd.append("password", pw);

            hf.ajax("POST", fd, "phpSrc/login.php", function(res)
            {
                if (res)
                {
                    var
                    r = JSON.parse(res);
                    if (r.code)
                    {
                        //Logged in!
                        console.log(r.message);
                        hf.elCN("loginerror")[0].innerText = r.message;

                        document.body.innerHTML = "";
                        sendMessage.init();
                        listMessages.init();
                        listContacts.init();

                    }
                    else
                    {
                        //Something failed
                        console.log(r.message);
                        hf.elCN("loginerror")[0].innerText = r.message;
                    }
                }
            })
            


        }
        return{
            click: function(ev)
            {
                var
                e = ev.target,
                loginBox = hf.elCN("login-box")[0];

                if (hf.isInside(e, loginBox))
                {
                    var
                    un = loginBox.getElementsByTagName("input")[0],
                    pw = loginBox.getElementsByTagName("input")[1],
                    sub = loginBox.getElementsByTagName("button")[0];
                    if (e == un)
                    {

                    }
                    else if (e == pw)
                    {

                    }
                    else if (e == sub)
                    {
                        submitCreds(un.value, pw.value);
                    }
                }
            }
        };
    })();
    return {
        clicked: function(ev)
        {
            login.click(ev);
            sendMessage.click(ev);
            listMessages.click(ev);
            listContacts.click(ev);
        }
    };
})();

window.onclick = function(ev)
{
    APP.clicked(ev);
}