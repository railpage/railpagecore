<!DOCTYPE HTML>
 <html style="font-family: sans-serif;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
 <head>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <title>{$newsletter.subject}</title>
 
 <link rel="stylesheet" type="text/css" href="https://www.railpage.com.au/content/email/style.normalise.css">
 <link rel="stylesheet" type="text/css" href="https://www.railpage.com.au/content/email/style.railpage.css">
 
 </head>
 
 <body style="margin: 0;background: #eee;line-height: 1.4;">
     {if isset($newsletter.action.view)}
 	<script type="application/ld+json">
     {
       "@context": "http://schema.org",
       "@type": "EmailMessage",
       "action": {
         "@type": "ViewAction",
         "url": "{$newsletter.action.view.url}",
         "name": "{$newsletter.action.view.name}"
       },
       "description": "{$newsletter.action.view.description}"
     }
     </script>
     {/if}
     
 	<div class="wrapper" style="width: 620px;margin: auto;background: white;box-shadow: 0px 0px 10px #999;">
     	<table class="header" style="border-collapse: collapse;border-spacing: 0;padding: 0;display: table;width: 100%;">
         	<tr>
             	<td class="logo" style="padding: 20px 10px 20px 20px;height: 100px;width: 100px;vertical-align: top;">
 		        	<a href="http://www.railpage.com.au" style="background-color: transparent;"><img src="https://static.railpage.com.au/i/logo-fb.jpg" height="100" style="border: none;height: 100px;max-height: 100px;"></a>
                 </td>
                 <td class="subject" style="padding: 20px 20px 20px 10px;">
 	            	<span class="heading" style="font-size: 20pt;display: block;">{$newsletter.subject}</span>
                     {if isset($newsletter.subtitle)}<span class="subtitle" style="color: #666;display: block;">{$newsletter.subtitle}</span>{/if}
                 </td>
             </tr>
         </table>
         {if isset($newsletter.hero) && isset($newsletter.hero)}
         <div class="hero" style="background: #333;min-height: 200px;max-height: 500px;overflow:hidden;">
         	<a href="{$newsletter.hero.url.canonical}" style="background-color: transparent;">
             {foreach $newsletter.hero.sizes as $size}
             {if $size.width >= 600px}
             <img src="{$size.source}" style="margin:auto;max-width: 100%;outline: none !important;text-decoration: none;-ms-interpolation-mode: bicubic;width: auto;clear: both;display: block !important;float: none;border: 0 !important;max-height: 500px;">
             {break}
             {/if}
             {/foreach}
 			</a>
         </div>
         {/if}
         
         {foreach $newsletter.content as $row}
         {if empty($row.text)}{continue}{/if}
         <div class="content" style="padding: 20px;">
         	<h2 style="font-size: 20px;margin: 0 0 10px 0;padding: 0;font-weight: normal;line-height: 1.4;background: transparent !important;border: none !important;margin-top: 0;">{$row.subtitle}</h2>
             {if isset($row.alt_title)}<p style="color: #989595;font-size: 13px;">{$row.alt_title}</p>{/if}
             
             <div style="margin: 0;padding: 0;font-size: 14px;line-height: 1.6;color: #565555;">
             
                 {if isset($row.featuredimage)}
                 <a target="_blank" href="{$row.link}"><img src="{$row.featuredimage}" style="border:none;max-width: 200px;max-height: 200px; margin-top:5px;float:left;margin-bottom:1em;margin-right:1em;outline: none !important;text-decoration: none;-ms-interpolation-mode: bicubic;width: auto;clear: both;display: block !important;border: 0 !important;"></a>
                 {/if}
                 
                 {$row.text}
             </div>
             {if isset($row.link) && !empty($row.link)}
             <br>
             <a target="_blank" href="{$row.link}" class="link2" style="color: #ffffff;text-decoration: none;border-top: 10px solid #27A1E5;border-bottom: 10px solid #27A1E5;border-left: 18px solid #27A1E5;border-right: 18px solid #27A1E5;border-radius: 3px;-moz-border-radius: 3px;-webkit-border-radius: 3px;background: #27A1E5;">{if isset($row.linktext) && !empty($row.linktext)}{$row.linktext}{else}Read more{/if}</a>
             {/if}
 
         </div>
         {/foreach}
         
         <div class="footer" style="margin-top:10px; font-size: 10pt;border-top: 1px solid #ddd;padding: 10px;text-align: center;color: #666;">
         	<p style="margin-top: 0;">Sent by Railpage Australia - <a href="http://www.railpage.com.au" style="background-color: transparent;">http://www.railpage.com.au</a></p>
         	{if isset($newsletter.hero) && isset($newsletter.hero)}<span class="author">Photo by {$newsletter.hero.author.username}</span>{/if}
         </div>
     </div>
 </body>
 </html>