<div class="avatar-container">
    <div class="avatar"><p>Change Avatar</p></div>
    <input type="file" name="avatar-input" class="avatar-input" hidden>
</div>
<h1>
    <div class="settings-username"></div>
</h1>
<input type="text" class="settings-displayname" placeholder="Display Name">
<!-- <input type="text" class="settings-display-name"> -->
<h2>Change Password</h2>
<!-- <input type="password" class="settings-oldpw" placeholder="Old Password"> -->
<input type="password" class="settings-newpw" placeholder="New Password">
<input type="password" class="settings-newpw-double" placeholder="Type New Password Again">
<div class="button-container">
    <button class="settings-changepw-button">Change</button>
</div>

<!-- <h2>Manage Your Own Credentials</h2> -->
<!-- <button class="settings-download-creds">Download Credentials</button> -->

<h2>Decrypt and Download Your Messages</h2>
<div class="button-container">
    <button class="settings-download-messages">Download Messages</button>
</div>

<label for="mPerPage"><h2>Messages Per Page</h2></label>
<input type="number" min="1", max="25" class="mPerPage">