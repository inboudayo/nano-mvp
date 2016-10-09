# Nano-MVP

Nano is an MVP-based framework, ideal for organizing projects with virtually no learning curve and complete flexibility.

## Installation

Nano requires PHP >= 5.4. After downloading, you'll find the following file structure.

* **lib**
  * **framework**
    * **controllers**
      * IndexController.php
    * **models**
    * **views**
      * footer.php
      * header.php
      * home.php
    * FormHandler.php
    * ModelFactory.php
    * Router.php
    * View.php
  * bootstrap.php
* **public**
  * **img**
  * **js**
  * **ss**
  * index.php
  * .htaccess

The **lib** folder should be located just outside of the root directory in a non web accessible folder for extra security. The contents of the **public** folder can be copied to your root directory. If you decide to rename the folders or place them in alternate locations, then both the path to `bootstrap.php` in `index.php` and the `RewriteBase` path in `.htaccess` will need to be updated.

*Default settings:*
```
// index.php
require('../lib/bootstrap.php');

// .htaccess
RewriteBase /
```
*Alternate settings:*
```
// index.php
require('../../nano_lib/bootstrap.php');

// .htaccess
RewriteBase /dev/nano/
```

Once you have made sure that these paths are correct, the framework should now be up and running. The template can be customized by editing both the header and footer in the **views** folder, and the css/javascript in the **public** folder. The optional global configuration settings can be found in `bootstrap.php`.

## How It Works

Nano is based on an [MVP (Model-View-Presenter)](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93presenter) design pattern, which is a spin-off of the popular [MVC (Model-View-Controller)](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller) pattern. The user interacts with a view; new requests are routed to a controller (or *presenter*) which may optionally communicate with any number of model layers, such as a database; the information is then fed back to the user in the form of another view and the whole process starts over.

Nano's router is little more than a stand-alone [front controller](https://en.wikipedia.org/wiki/Front_controller), so routes are implicitly tied to the application's design and more or less handled for you. As you might expect, the path provided in the URL determines which route to take. In the following URL, for example, the page itself can be considered a controller, the action specifies a method (or function) to execute inside of that controller, and any additional parameters would be the method's arguments.

`http://example.com/controller.php?action=method&foo=arg1&bar=arg2`

In order to get more semantic or friendly URLs, `.htaccess` directs all incoming requests to `index.php`. This file loads the `bootstrap.php` configuration which then calls the router, appropriately named `Router.php`. We can now update the previous URL as follows:

`http://example.com/controller/method/arg1/arg2`

It is important to note that `.htaccess` has technically not done any rewriting here, the semantic format is simply what is expected and the router takes care of the rest. This allows you the freedom to mix both semantic and non-semantic URLs if you choose although non-semantic URLs won't be routed.

The path provided in the URL is evaluated from left to right. Based on the preceding example, if the controller does not exist then it will be checked as a method within the default controller, `IndexController.php`. The URL then becomes:

`http://example.com/method/arg1/arg2/arg3`

Likewise, if the method does not exist then it is checked as an additional argument passed to the default method within the controller:

`http://example.com/arg1/arg2/arg3/arg4`

Finally, if the total number of arguments in the path appear to conflict with the required or optional number of parameters defined by the method then it will automatically trigger a 404 (page not found). When a valid route is found, the controller can process the request and feed the user another view.

## A Basic Example

In order to fully understand how everything ties together it is best to look at a practical real-world example. One of the first things a new website needs is a contact form. While it may not seem like that complicated of a task, there is actually quite a bit to consider. Additionally, Nano provides a small handful of built-in functions, and while building our example form we can briefly touch on all but one of them (pagination) which makes it a great example to start with.

In order to create a new page you first need to identify which controller to use, and if it doesn't exist, create it. A contact form is perfectly suitable in the default `IndexController.php` so in this case you just need to add a new method. For this example, the method will contain one optional argument named `$action` that will act as a flag to let us know whether the form is being viewed or submitted.

```php
<?php
// call the view for the contact form
public function contact($action = false)
{
    // process form if submitted
    $form = new FormHandler();
    if ($action) {
        if ($action != 'submit') {
            (new Router)->notFound();
        }

        // validate input
        // ...
    }

    // display form
    $this->view->data['page_title'] = 'Contact';
    $this->view->data['description'] = 'This is the page description.';
    $this->view->data['keywords'] = 'these, are, the, page, keywords';

    $this->view->load('contact', $form);
}
```

The first step is to initialize the `FormHandler` object which will help make error handling easier. Next, if an `$action` has been supplied but does not match the value expected you can manually trigger a 404 with the router's `notFound()` method. If no argument was supplied, then we can safely assume the form has not been submitted and proceed to load the view. Any custom data that this particular view needs can be set in the `$this->view->data` property. The second parameter passed to `$this->view->load()` is only necessary when the view contains a form, and it must be an instance of the `FormHandler` object. Behind the scenes, this object has already created a unique [CSRF (Cross-Site Request Forgery)](https://en.wikipedia.org/wiki/Cross-site_request_forgery) token for security and preserved all form data (if a previous submission failed) in the current session to save the user from having to re-type everything.

If we do have a valid `$action` flag, the next step is verify that a form has actually been submitted and then validate the user supplied data.

```php
<?php
// validate input
if ($form->submit($_POST['csrf'])) {
    $name = $form->validate($_POST['name'], null, 50) ? $_POST['name'] : null;
    $website = $form->validate($_POST['website'], 'url', 255) ? $_POST['website'] : 'n/a';
    $email = $form->validate($_POST['email'], 'email', 255) ? $_POST['email'] : null;
    $comments = $form->validate($_POST['comments']) ? $_POST['comments'] : null;

    // proceed if required fields were validated
    if (isset($name, $email, $comments)) {
        // send message
        // ...
    } else {
        // save errors
        if (!isset($name)) {
            $form->error('name', 'Please enter your name.');
        }
        if (!isset($email)) {
            $form->error('email', 'Please enter a valid e-mail.');
        }
        if (!isset($comments)) {
            $form->error('comments', 'Please enter your comments.');
        }
    }
}

(new Router)->redirect('contact');
```

The `$form->submit()` method accepts one argument, the CSRF token. In addition to verifying the token against the session value, this method also checks for a valid `POST` request. If the submission fails, then we assume it's a forged request so no errors are set and the user is brought back to the form with the router's alternative `redirect()` method. This redirect also prevents the form from attempting to resubmit itself if the user tries to go back a page. If the submission is successful, the `$form->validate()` method can be used to check user input. If any `null` values are found for our required fields then we can set custom errors with the `$form->error()` method.

In order to send the e-mail, you could simply use PHP's built-in `mail()` function; however, in order to demonstrate how models work (and using third-party libraries instead of building your own) I have downloaded the [PHPMailer](https://github.com/PHPMailer/PHPMailer) `class.phpmailer.php` and `class.smtp.php` files and placed them in the **models** folder. In order to follow Nano's conventions, the filename of models should also match the name of the class defined within, and the first letter capitalized. Any dependencies outside of the namespace must be explicitly declared or required. To acheive this, I renamed `class.phpmailer.php` to `Phpmailer.php` and added the following lines at the top of the file.

```php
<?php
namespace framework\models;

use Exception,
    SMTP;

require('class.smtp.php');
```

With the model now accessible, we can proceed as follows:

```php
<?php
// send message
$mail = $this->model->build('phpmailer');
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'username';
$mail->Password = 'password';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;

$mail->setFrom('no-reply@nanomvp.com');
$mail->addAddress('support@nanomvp.com');
$mail->addReplyTo($email, $name);
$mail->isHTML(true);
$mail->Subject = $_SERVER['SERVER_NAME'] . ' - Contact Form';
$mail->Body = "Website: $website<br /><br />$comments";

if ($mail->Send()) {
    $form->success('Your message was sent successfully.');
} else {
    // save errors
    $form->error('PHPMailer failed: ' . $mail->ErrorInfo);
}
```

Although it isn't demonstrated in this example, `$this->model->build()` also accepts an optional second parameter (similar to `$this->view->load()`) which defaults to `false`. If set to `true` it indicates that the model requires a database connection and the credentials are looked up in `bootstrap.php`.

After confirming the mail has been sent, a success message is set with `$form->success()` and the user will still (based on the previous example) be redirected back to the form. If an error was encountered, we can again call `$form->error()` only this time passing it a single value instead of a key/value pair.

Now that the code for the controller is complete, all that's left is the view. Since the header and footer are already in place, the only code needed is for the form itself which I will place in a file called `contact.php` in the **views** folder. Any information from `$this->view->data` that was defined in the controller is now available for access in `$this->data`. For example, I am accessing the page title and keywords in the header. The only other information is form data which has been passed in as an object, we just have to put it to use.

```php
<?php $form->status(); ?>

<form action="contact/submit" method="POST">
<fieldset>
<legend>Your Information</legend>
<label for="name">Name:</label>
<input <?php if (in_array('name', $form->failed)) { echo 'class="form-error" '; } ?>type="text" name="name" value="<?php if (isset($form->data['name'])) { echo $form->data['name']; } ?>" maxlength="50" required="required" autofocus="autofocus" placeholder="your name" /><br /><br />

<label for="website">Website:</label>
<input <?php if (in_array('website', $form->failed)) { echo 'class="form-error" '; } ?>type="url" name="website" value="<?php if (isset($form->data['website'])) { echo $form->data['website']; } ?>" maxlength="255" placeholder="http://" /><br /><br />

<label for="email">E-mail:</label>
<input <?php if (in_array('email', $form->failed)) { echo 'class="form-error" '; } ?>type="email" name="email" value="<?php if (isset($form->data['email'])) { echo $form->data['email']; } ?>" maxlength="255" required="required" placeholder="your@email.com" /><br /><br />

<label for="comments">Comments:</label>
<textarea <?php if (in_array('comments', $form->failed)) { echo 'class="form-error" '; } ?>name="comments" rows="10" cols="50" required="required" placeholder="your comments"><?php if (isset($form->data['comments'])) { echo $form->data['comments']; } ?></textarea><br /><br />

<input type="hidden" name="csrf" value="<?php echo $form->token; ?>" />
<input type="submit" value="Submit" />
</fieldset>
</form>
```

The method `$form->status()` outputs any success or error messages and cleans up data that is no longer needed. The form is set to submit back to itself with the argument "submit" which will be caught by the controller's `$action`. I then go on to compare field names against `$form->failed` to help highlight errors, and access `$form->data` to repopulate any preserved data (this has all been sanitized behind the scenes). The last property, `$form->token`, accesses our unique CSRF token.

You should now have a fully-functional contact form and (hopefully) a more in-depth understanding of Nano.

##License

The Nano MVP framework is licensed under the [MIT license](http://opensource.org/licenses/MIT).
