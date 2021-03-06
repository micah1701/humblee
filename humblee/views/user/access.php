<h2 class="title">Website Access Log</h2>
<p class="subtitle">Here is a list of your most recent successful log in attempts</p>
<a class="button is-info" href="<?php echo _app_path ?>user/profile">Return to Profile</a>
<table class="table is-striped is-hoverable">
    <thead>
        <th>Date</th>
        <th>Time</th>
        <th>IP Address</th>
        <th>Browser/Device</th>
    </thead>
    <tbody>
<?php
    foreach($userAccessLog as $accessAttempt)
    {
?>
        <tr>
            <td><?php echo date("m/d/Y",strtotime($accessAttempt->timestamp)); ?></td>
            <td><?php echo date("g:ia",strtotime($accessAttempt->timestamp)); ?></td>
            <td><?php echo $accessAttempt->ip_address; 
                if($accessAttempt->ip_geolocation != "")
                {
                    echo "<br>".$accessAttempt->ip_geolocation;
                }
            ?>
            </td>
            <td><?php echo $accessAttempt->user_agent; ?></td>
        </tr>
<?php
        }
    ?>
    </tbody>
</table>