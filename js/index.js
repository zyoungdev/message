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
            cN: function(e,cl)
            {
                return e.className == cl;
            },
            cEL: function(el, attr)
            {
                var node = document.createElement(el);
                if (attr)
                {
                    for (var a in attr)
                    {
                        if (attr.hasOwnProperty(a))
                        {
                            node.setAttribute(a, attr[a]);
                        }
                    }
                }
                if (arguments[2])
                    node.innerHTML = arguments[2];
                return node;
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
    contactList = (function()
    {
        var
        contactList,
        checkBoxIndexes = [],
        buildList = function()
        {
            hf.ajax("GET", null, "templates/contactList.php", function(res)
            {
                var
                container = hf.cEL("div", {class: "module-container contact-list-container"}),
                frag = document.createDocumentFragment(),
                containerExists = hf.elCN("contact-list-container")[0];

                container.innerHTML = res;

                if (!containerExists)
                    document.body.appendChild(container);
                else
                    hf.elCN("contact-list")[0].innerHTML = "";
                
                if (contactList["code"] == 0) return;

                for (var user in contactList)
                {
                    var
                    selBut = hf.cEL("input", {class: "contact-checkbox", type: "checkbox"}),
                    contact = hf.cEL("div", {class: "contact"}),
                    username = hf.cEL("div", {class: "contact-username"}, user);
                    // delBut = hf.cEL("button", {class: "contact-delete-button"}, "Delete");

                    contact.appendChild(selBut);
                    contact.appendChild(username);
                    // contact.appendChild(delBut);

                    frag.appendChild(contact);
                }
                hf.elCN("contact-list")[0].appendChild(frag);
            });
        },
        getList = function()
        {
            hf.ajax("GET", null, "phpSrc/listContacts.php", function(res)
            {
                contactList = JSON.parse(res);
                // console.log(contactList);
                buildList();
            });
        },
        addContact = function(u)
        {
            // console.log("yay");
            var
            fd = new FormData();

            fd.append("contact", u);

            hf.ajax("POST", fd, "phpSrc/addContact.php", function(res)
            {
                console.log(res);
                hf.ajax("GET", null, "phpSrc/listContacts.php", function(r)
                {
                    contactList = JSON.parse(r);
                    if (contactList["code"] == null)
                    {
                        // console.log(contactList);
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
                // console.log(res);
                // res = JSON.parse(res);
                if (res["code"] == null)
                {
                    delete contactList[u];
                    buildList();
                }
                else
                {
                    // console.log(res);
                }
            });
        },
        deleteMultipleContacts = function()
        {
            console.log(contactList);
            var
            newContactList = {},
            fd = new FormData(),
            backupContactList = contactList,
            checkboxes = hf.elCN("contact-checkbox"),
            delBut = hf.elCN("delete-multiple-contact-button")[0],
            contactListContainer = hf.elCN("contact-list")[0];

            checkBoxIndexes = [];
            for (var i = 0, len = checkboxes.length; i < len; i++)
            {
                if (!checkboxes[i].checked)
                {
                    var
                    user = checkboxes[i].parentNode.children[1].innerText;
                    newContactList[user] = contactList[user];
                }
            }
            contactList = newContactList;
            fd.append("contacts", JSON.stringify(contactList));
            hf.ajax("POST", fd, "phpSrc/deleteMultipleContacts.php", function(res)
            {
                console.log(res);
                res = JSON.parse(res);

                if (res["code"] == 0)
                {
                    contactList = backupContactList;
                }
                delBut.style.display = "none";
                buildList();
            });
        },
        checkboxClick = function(e)
        {
            var
            checkboxes = hf.elCN("contact-checkbox"),
            delBut = hf.elCN("delete-multiple-contact-button")[0];
            console.log(delBut);

            //if any are selected view delete button
            for (var i = 0, len = checkboxes.length; i < len; i++)
            {
                if (checkboxes[i].checked)
                {
                    delBut.style.display = "block";
                    return;
                }
            }
            delBut.style.display = "none";
        }
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
                    if (hf.cN(e, "contact-username"))
                    {
                        var user = ev.target.innerText;

                        sendMessage.init(user);
                    }
                    else if (hf.cN(e, "add-contact-button"))
                    {
                        var
                        user = hf.elCN("add-contact-input")[0].value;
                        addContact(user)
                    }
                    else if (hf.cN(e, "contact-checkbox"))
                    {
                        checkboxClick(e);
                    }
                    else if (hf.cN(e, "delete-multiple-contact-button"))
                    {
                        deleteMultipleContacts();
                    }
                }
            }
        };
    })(),
    sendMessage = (function()
    {
        var
        imgs = [],
        sendPlaintext = function(rec, pt, el)
        {
            if (imgs.length > 0)
            {
                pt += "<div class=view-message-images-container>";
                for (var i = 0, len = imgs.length; i < len; i++)
                {
                    pt += imgs[i];
                }
                pt += "</div>";
            }
            if (pt === "") return;
            var
            fd = new FormData();


            fd.append("plaintext", pt);
            fd.append("recipient", rec);

            hf.ajax("POST", fd, "phpSrc/sendMessage.php", function(res)
            {
                // console.log("Response recieved for sent message");
                document.body.removeChild(el);
                imgs = [];
                contactList.init();
            });
        },
        addImage = function()
        {
            var
            fileInput = hf.elCN("file-upload-input")[0];
            fileInput.click();

            fileInput.onchange = function()
            {
                var
                file = fileInput.files[0],
                img = new Image();

                img.file = file;

                var reader = new FileReader();
                    reader.onload = (function(aImg) 
                        { 
                            return function(e) 
                                { 
                                    // console.log(e.target.result);
                                    aImg.src = e.target.result; 

                                    var
                                    i = "<img src=";
                                    i += e.target.result;
                                    i += ">";
                                    
                                    imgs.push(i);
                                    // console.log(imgs);
                                }; 
                        })(img);
                    reader.readAsDataURL(fileInput.files[0]);

                // console.log(fileInput.files);
            }
        },
        closeMessage = function(e)
        {
            for (var i = 0, len = imgs.length; i < len; i++)
            {
                imgs[i] = "0".repeat(imgs[i].length);
            }
            imgs = [];
            document.body.removeChild(e);
        }
        return{
            init: function(rec)
            {
                hf.ajax("GET", null, "templates/sendMessage.php", function(res)
                {
                    var
                    container = document.createElement("div");
                    container.className = "module-container send-message-box";
                    container.innerHTML = "";

                    var elemExists = hf.elCN("send-message-box")[0]
                    if (!elemExists)
                        document.body.appendChild(container);
                    container.innerHTML = res;

                    hf.elCN("send-message-button").disabled = false;
                    if (rec)
                        hf.elCN("send-message-box")[0].children[0].value = rec;
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
                    img = sendMessageBox.getElementsByTagName("button")[0],
                    sub = sendMessageBox.getElementsByTagName("button")[1],
                    dis = sendMessageBox.getElementsByTagName("button")[2];
                    if (e == ta)
                    {

                    }
                    else if (e == img)
                    {
                        addImage();
                    }
                    else if (e == sub)
                    {
                        sendPlaintext(rec.value, ta.value,e.parentNode);
                        e.disabled = true;
                    }
                    else if (e == dis)
                    {
                        closeMessage(e.parentNode);
                    }
                }
            }
        };
    })(),
    messageView = (function()
    {
        var
        currentMessage,
        buildView = function()
        {
            hf.ajax("GET", null, "templates/viewMessage.php", function(res)
            {
                var
                container = hf.cEL("div", {class: "module-container view-message-container"}),
                frag = document.createDocumentFragment(),
                date = new Date(currentMessage["timestamp"] * 1000),
                containerExists = hf.elCN("view-message-container")[0];

                container.innerHTML = res;

                if (!containerExists)
                {
                    document.body.appendChild(container);
                }
                else
                {
                    hf.elCN("view-message-sender")[0].innerHTML = "";
                    hf.elCN("view-message-timestamp")[0].innerHTML = "";
                    hf.elCN("view-message-message")[0].innerHTML = "";
                }
                
                if (contactList["code"] == 0) return;

                hf.elCN("view-message-sender")[0].innerHTML = currentMessage["sender"];
                hf.elCN("view-message-timestamp")[0].innerHTML = date.toLocaleString();
                hf.elCN("view-message-message")[0].innerHTML = currentMessage["plaintext"];
            });
        },
        deleteMessage = function(e)
        {
            messageList.deleteMessage(currentMessage["sender"], currentMessage["timestamp"]);
        },
        closeMessage = function(e)
        {
            document.body.removeChild(e.parentNode.parentNode);
            
            //Clear the plaintext from memory, A little heavy handed
            currentMessage["plaintext"] = "0".repeat(currentMessage["plaintext"].length);
            currentMessage = {};
        }
        return{
            init: function(res)
            {
                currentMessage = res;
                buildView();
            },
            click: function(ev)
            {
                var
                e = ev.target,
                viewMessageContainer = hf.elCN("view-message-container")[0],
                index;
                if (viewMessageContainer)
                    index = Array.prototype.indexOf.call(viewMessageContainer.children, e.parentNode);

                if (hf.isInside(e, viewMessageContainer))
                {
                    if (hf.cN(e, "view-message-reply-button"))
                    {
                        sendMessage.init(currentMessage["sender"]);
                    }
                    else if (hf.cN(e, "view-message-delete-button"))
                    {
                        deleteMessage(e);
                        closeMessage(e);
                    }
                    else if (hf.cN(e, "view-message-close-button"))
                    {
                        closeMessage(e);
                    }

                }
            }
        }
    })(),
    messageList = (function()
    {
        var
        messageList,
        buildList = function()
        {
            hf.ajax("GET", null, "templates/messageList.php", function(res)
            {
                var
                container = hf.cEL("div", {class: "module-container message-list-container"}),
                frag = document.createDocumentFragment(),
                containerExists = hf.elCN("message-list-container")[0];

                container.innerHTML = res;

                if (!containerExists)
                    document.body.appendChild(container);
                else
                    hf.elCN("message-list")[0].innerHTML = "";
                
                if (messageList["code"] == 0) return;

                for (var user in messageList)
                {
                    for (var message in messageList[user])
                    {
                        var
                        msg = hf.cEL("div", {class: "message"}),
                        username = hf.cEL("div", {class: "message-username"}, user),
                        date = new Date(messageList[user][message].timestamp * 1000),
                        timestamp = hf.cEL("div", {class: "message-timestamp"}, date.toLocaleString()),
                        delBut = hf.cEL("button", {class: "message-delete-button"}, "Delete");

                        msg.appendChild(username);
                        msg.appendChild(timestamp);
                        msg.appendChild(delBut);

                        frag.appendChild(msg);
                    }
                }
                hf.elCN("message-list")[0].appendChild(frag);
            });
        },
        viewMessage = function(u,t)
        {
            var
            fd = new FormData();

            fd.append("username", u);
            fd.append("timestamp", t);

            hf.ajax("POST", fd, "phpSrc/viewMessage.php", function(res)
            {
                res = JSON.parse(res);

                messageView.init(res);                
            });
        },
        delMessage = function(u, t)
        {
            var
            fd = new FormData();

            fd.append("timestamp", t);
            fd.append("username", u);

            hf.ajax("POST", fd, "phpSrc/deleteMessage.php", function(res)
            {
                // console.log(res);
                // res = JSON.parse(res);
                if (res["code"] == null)
                {
                    delete messageList[u][t];
                    buildList();
                }
                else
                {
                    // console.log(res);
                }
            });
        },
        getList = function()
        {
            hf.ajax("GET", null, "phpSrc/listMessages.php", function(res)
            {
                messageList = JSON.parse(res);
                buildList();
            });
        };

        return{
            init: function()
            {
                getList();
            },
            deleteMessage: function(user,time)
            {
                delMessage(user,time);
            },
            click: function(ev)
            {
                var
                e = ev.target,
                messageListContainer = hf.elCN("message-list-container")[0];

                if (hf.isInside(e, messageListContainer))
                {
                    if (hf.cN(e.parentNode, "message"))
                    {
                        // var index = Array.prototype.indexOf.call(messageListContainer.children, e.parentNode);
                        var
                        user = e.parentNode.children[0].innerText,
                        time = new Date(e.parentNode.children[1].innerText).getTime() / 1000;

                        if (hf.cN(e, "message-delete-button"))
                        {
                            this.deleteMessage(user, time);
                        }
                        else
                        {
                            viewMessage(user, time);
                        }
                    }
                    else if (hf.cN(e, "refresh-messages-button"))
                    {
                        getList();
                    }
                    else if (hf.cN(e, "create-message-button"))
                    {
                        sendMessage.init();
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
                        // console.log(r.message);
                        hf.elCN("loginerror")[0].innerText = r.message;

                        document.body.innerHTML = "";
                        // sendMessage.init();
                        messageList.init();
                        contactList.init();

                    }
                    else
                    {
                        //Something failed
                        // console.log(r.message);
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
            messageList.click(ev);
            contactList.click(ev);
            messageView.click(ev);
        }
    };
})();

window.onclick = function(ev)
{
    APP.clicked(ev);
}