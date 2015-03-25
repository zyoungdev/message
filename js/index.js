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
                        console.log(r.message);
                        hf.elCN("loginerror")[0].innerText = r.message;
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
                loginBox = hf.elCN("login-box")[0],
                un = loginBox.getElementsByTagName("input")[0],
                pw = loginBox.getElementsByTagName("input")[1],
                sub = loginBox.getElementsByTagName("button")[0];

                if (hf.isInside(e, loginBox))
                {
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
        }
    };
})();

window.onclick = function(ev)
{
    APP.clicked(ev);
}