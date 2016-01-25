<!DOCTYPE HTML>
<html style="font-family: sans-serif;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>{$email.subject}</title>

</head>

<body style="margin: 0;background: #eee;line-height: 1.4;">
	<div class="wrapper" style="width: 620px;margin: auto;background: white;box-shadow: 0px 0px 10px #999;">
    	<table class="header" style="border-collapse: collapse;border-spacing: 0;padding: 0;display: table;border-bottom: 1px solid #ddd;width: 100%;">
        	<tr>
            	<td class="logo" style="padding: 20px 10px 20px 20px;height: 100px;width: 100px;vertical-align: top;">
		        	<a href="http://www.railpage.com.au" style="background-color: transparent;"><img src="https://static.railpage.com.au/i/logo-fb.jpg" height="100" style="border: none;height: 100px;max-height: 100px;"></a>
                </td>
                <td class="subject" style="padding: 20px 20px 20px 10px;">
	            	<span class="heading" style="font-size: 20pt;display: block;">{$email.subject}</span>
                    {if isset($email.subtitle)}<span class="subtitle" style="color: #666;display: block;">{$email.subtitle}</span>{/if}
                </td>
            </tr>
        </table>
        {if isset($email.hero) && isset($email.hero.image)}
        <div class="hero" style="background: #333;min-height: 200px;max-height: 400px;">
        	<img src="{$email.hero.image}" style="border: none;max-width: 620px;max-height: 400px;display: block;">
        </div>
        {if isset($email.hero.title)}
        <div class="hero-title" style="padding: 10px;display: block;font-size: 14pt;color: white;background: #111;">{if isset($email.hero.link)}<a href="{$email.hero.link}" style="background-color: transparent;color: white;text-decoration: none;">{/if}{$email.hero.title}{if isset($email.hero.link)}</a>{/if}</div>
        {/if}
        {/if}
        
		{foreach $email.items as $item}
        <div class="content" style="padding: 20px;">
        	<h2 style="margin-top: 0;">{if isset($item.link)}<a href="{$item.link}" style="background-color: transparent;color: #06C;text-decoration: none;">{/if}{$item.title}{if isset($item.link)}</a>{/if}</h2>
			{if isset($item.subtitle)}<span class="subtitle" style="display: block;color: #666;letter-spacing: 1px;">{$item.subtitle}</span>{/if}
			
			{if isset($item.featured_image) && !empty($item.featured_image)}
			<img src="{$item.featured_image}" class="featured" width="100" style="border: none;max-height: 100px;min-height: 50px;float: left;margin-right: 20px;margin-bottom: 20px;margin-top:20px;">
			{/if}
			
			{$item.text}
			{if isset($item.link)}<div><a href="{$item.link}" class="link" style="background-color: transparent;display: inline-block;padding: 10px;border-radius: 5px;color: white;background: #036;text-decoration: none;margin-top: 20px;">Read more</a></div>{/if}
        </div>
		{/foreach}
        
        <div class="footer" style="font-size: 10pt;border-top: 1px solid #ddd;padding: 10px;text-align: center;color: #666;">
        	<p style="margin-top: 0;">Sent by Railpage Australia - <a href="http://www.railpage.com.au" style="background-color: transparent;">http://www.railpage.com.au</a></p>
        	{if isset($email.hero) && isset($email.hero.image)}<span class="author">Photo by {$email.hero.author}</span>{/if}
        </div>
    </div>
</body>
</html>