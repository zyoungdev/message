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
            contactListContainer = hf.elCN('contact-list-container')[0];
            for (var user in contactList)
            {
                var
                contact = document.createElement("div"),
                userDiv = document.createElement("div");

                contact.className = "contact";
                userDiv.innerText = user;

                contact.appendChild(userDiv);
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
                div.className = "module-container contact-list-container";
                document.body.appendChild(div);

                contactList = JSON.parse(res);
                console.log(contactList);
                buildList();
            });
        };
        return {
            init: function()
            {
                getList();

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
            frag = document.createDocumentFragment(),
            listContainer = hf.elCN("message-list-container")[0];

            for (var user in messageList)
            {
                var
                userFrag = document.createDocumentFragment();
                
                

                for (var message in messageList[user])
                {
                    var
                    time = document.createElement("div"),
                    messageDiv = document.createElement("div"),
                    username = document.createElement("div");

                    messageDiv.className = "message-in-list";
                    username.innerText = user;

                    time.innerText = messageList[user][message].timestamp;

                    messageDiv.appendChild(username);
                    messageDiv.appendChild(time);
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
        getList = function()
        {
            hf.ajax("GET", null, "phpSrc/listMessages.php", function(res)
            {
                var
                div = document.createElement("div");
                div.className = "module-container message-list-container";
                document.body.appendChild(div);

                messageList = JSON.parse(res);
                console.log(messageList);
                buildList();
            });
        }

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

                        viewMessage(user, time);
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
        }
    };
})();

window.onclick = function(ev)
{
    APP.clicked(ev);
}