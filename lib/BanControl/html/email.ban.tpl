<h2>Railpage account suspension</h2>

<p>Hi {$userdata_username},</p>
<p>Your Railpage account has been suspended. The reason for the suspension is as follows:</p>
<p>{$ban_reason}</p>
{if isset($ban_expire) && $ban_expire > 0}<p>Your account will be re-activated on or after {$ban_expire_nice}</p>{/if}
<p>Regards,<br />
The Railpage team.</p>