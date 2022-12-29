=== Email Validation Filter for Contact Form 7 ===
Contributors: asamaruk
Tags: contact form validation, contact form spam, wordpress spam blocker, validation, spam, spam email, email spam, rfc, reject, dns
Requires at least: 5.0
Tested up to: 6.0
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 
Added mail validation function to Contact Form 7. Protected by rejection filter, RFC filter, and DNS filter.
 
== Description ==

Email Verification Filter for Contact Form 7 provides additional functionality to Contact Form 7's mail verification feature.
To use it, you need to install Contact Form 7.
The following three filters are provided, and this plugin will work after Contact Form 7 has been validated.

= Reject filter =

Specify the email address or domain of the user you want to reject, and restrict submissions from the form.
The target users can be the email addresses or domains specified in the administration screen.

= RFC Filter =

Restrict form submissions from email addresses that do not conform to RFC specifications and requirements.
A period [.] is the first character, an at mark [.] is the second character, and so on. is used at the beginning, before an at mark [@], or consecutively.

- example.@example.com ([. @]) The domain is correct, but a period [.] is entered before the at mark [@]. is typed before the at mark [@])
- example..123@example.com([...] typed in. The domain is correct, but a period [.] is entered in succession. is entered consecutively)

= DNS Filter =
Checks if the domain of the email address entered is registered with the DNS server and if it is a valid email address for submission.
The problematic email address will be restricted from submitting the form. The following email addresses that pass Contact Form 7 validation are restricted.

- example@example.comcom (Enter [comcom]. A domain that does not exist)
- example@example.com.com (Enter [.com.com]. Non-existent domain.)
 
== Installation ==
 
You can install this plugin directly from your WordPress dashboard:
 
 1. Please install the Contact Form 7 plug-in. [plugin's website](https://contactform7.com/).
 2. Go to the *Plugins* menu and click *Add New*.
 3. Search for *Email Validation Filter for Contact Form 7*.
 4. Click *Install Now* next to the *Email Validation Filter for Contact Form 7* plugin.
 5. Activate the plugin.

== Screenshots ==

 1. Email Validation Settings Page.

== Frequently Asked Questions ==
 
== Changelog ==

= 1.0.0 =
* 2022-04-01 First release