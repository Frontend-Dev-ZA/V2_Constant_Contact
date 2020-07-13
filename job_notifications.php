<?php
/*
Plugin Name: Job Notifications for Contstant Contact using API V2
Plugin URI: https://semeonline.co.za
Plugin Platform: Wordpress - Version: Stable release: 5.4.2
Plugin Date: July 2020 - "The Year of Covid-19"

Description: Automate sending of emails to users who have subscribed for a job post
Author: Nilesh Cara
Company URI: https://semeonline.co.za
Version: 1.0
Location: South Africa, Johannesburg
*/

/*
1. Get all users from the database
2. Create contacts on constant coontact (assign to a list or lists)
3. Get all new jobs sorted by dealine
4. Send jobs by category starting with all
*/

//Schedule actions if they not already scheduled
if ( ! wp_next_scheduled( 'CREATE_CONTACTS_DAILY' ) ) {
    wp_schedule_event( strtotime('06:00:00'), 'daily', 'CREATE_CONTACTS_DAILY' );
}

///Hook into that action that'll fire everyday at 06:00hrs
add_action( 'CREATE_CONTACTS_DAILY', 'createContacts' );

// create contacts
function createContacts() {
    
    $bearer_token = "xxxxxxxx";

    $constant_contact_list_id = array(
        "All Departments" => '',
        "Airport" => '',
        "Budget & Finance" => '',
        "Business Development" => '',
        
    );

    $contacts = [];
    foreach (get_subscribed_users() as $user) {

        $fields = clean_data($user->fields);
        $full_name = clean_field(',"id"', $fields[2]);
        $first_name = clean_field(',"middle"', $fields[5]);
        $last_name = clean_field('},{"name"', $fields[7]);
        $email = clean_field(',"id"', $fields[9]);
        $raw_departments = explode("\\n", clean_field(',"id"', $fields[14]));

        $department_ids = [];

        foreach($raw_departments as $department) {
            array_push($department_ids, json_encode(["id" => $constant_contact_list_id[$department]]));
        }
        array_push(
            $contacts,
            '{
                "lists": ['.implode(",",$department_ids).'],
                "email_addresses": [
                    {
                    "email_address": "' . $email.'"
                    }
                ],
                "first_name": "' . $first_name.'",
                "last_name": "' . $last_name .'"
            }'
        );
    }
    foreach($contacts as $contact){
        createContact($contact, $bearer_token);
    }
    // after creating contacts send the email notifications
    send_notification();
}

// get subscribed users created in the last 24 hours
function get_subscribed_users() {
    global $wpdb;
    //return an array of subscribed users
    $users = $wpdb->get_results( "SELECT form_id, fields  FROM vfaur_wpforms_entries WHERE form_id=26880 && date_modified > (NOW() - INTERVAL 24 HOUR);" );
    return $users;
}


function clean_field($junk, $field) {
    return str_replace('"', '', str_replace($junk, '', $field));
}

function clean_data($data) {
    $raw_data = str_replace(array('[', ']'),"", $data);
    return explode(":", $data);
}

function createContact($contact, $bearer_token) {
    $ch = curl_init( 'https://api.constantcontact.com/v2/contacts?action_by=ACTION_BY_OWNER&api_key= '); //input your onwer key 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $contact);
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $bearer_token,
        'Accept: application/json'
        ));
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    $result = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return array($http_status, json_decode($result));
}

// create email campaign and send it
function send_notification() {

    $bearer_token = ""; //input your bearer token key

    $jobs = get_jobs();
    $jobs_ordered_by_department = [];

    foreach($jobs as $key => $value){
        $jobs_ordered_by_department[$value['department']][$key] = $value;
    }

    if (count($jobs_ordered_by_department) > 0) {
        $message = "<ul>";
        foreach($jobs_ordered_by_department as $department) {
            foreach($department as $job) {
                $job_text = '<li>'.$job['job-department'] . ' - ' . '<a href=\"#\" rel=\"noopener noreferrer\" style=\"color:#000285;font-weight:normal;font-style:normal;text-decoration:underline\" target=\"_blank\">'.$job['title'].'</a>';
                $message .= $job_text;
            } 
        }
        $message .= "</ul>";

        $bytes = random_bytes(5);

        $EmailCampaign = createEmailCampaign($bearer_token, bin2hex($bytes), $message);
        if ($EmailCampaign[0] == 201) {
            sendCampaign($bearer_token, $EmailCampaign[1]->id);
        }
    }
}

function get_jobs() {
    $args = array(
        'post_type' => 'jobs',
        'date_query' => array(
            array(
                'after' => '24 hours ago'
            )
        )
    );
    
    $posts = get_posts($args);

    $jobs = [];
    foreach ($posts as $post) {
        array_push(
            $jobs,
            [
                "title" => $post->post_title,
                "slug" => $post->post_name,
                "deadline" => get_post_meta($post->ID)['deadline'][0],
                "job-department" => get_post_meta($post->ID)['job-department'][0]
            ]
        );
    }
        
    return $jobs;
}

function createEmailCampaign($bearer_token, $name, $jobs) {
    // $message = send_notification();
    
    $message = "<!DOCTYPE html><html><head><meta http-equiv=\"Content-Type\" content=\"text/html charset=ISO-8859-1\"/> </head><body><table class=\"main\" style=\"width:612px; text-align:center; margin: 0 auto; border: 0px;padding:0px;border-spacing:0px\"><tbody><tr><td class=\"mainCell\" style=\"text-align:center;vertical-align:top;margin:0 auto;padding:15px 5px\"><table class=\"mainInner\" style=\"width:100%;text-align:center;margin:0 auto;padding:0px;border-spacing:0px;border:0px\"><tbody><tr><td class=\"mainInnerCell\" style=\"text-align:center;margin:0 auto;padding:1px;vertical-align:top; background-color:#36495f\"><table class=\"mainInnerCellTable\" style=\"width:100%;text-align:center;margin:0 auto;border:0px;padding:0px;border-spacing:0px;background-color:#36495f\"><tbody><tr><td class=\"mainInnerCellTableCell\" style=\"text-align:center;margin:0 auto;vertical-align:top;background-color:#ffffff;padding:0px\"><div><table class=\"sectionTable topBanner\" style=\"width:100%;min-width:100%;padding:0px;border-spacing:0px\"><tbody><tr><td class=\"sectionCell topBanner\" style=\"background-color:#cf8a00;width:100%;text-align:center;vertical-align:top\"></td></tr></tbody></table><table class=\"sectionTable logoContainer\" style=\"width:100%;min-width:100%;padding:0px;border-spacing:0px\"><tbody><tr><td class=\"sectionCell logoContainer\" style=\"width:100%;text-align:center;vertical-align:top;background-color:#002856\"><div><table class=\"sectionCellTable logo\" style=\"min-width:100%;width:100%;padding:0px;border-spacing:0px;border:0px\"><tbody><tr><td class=\"logoImageCell\" style=\"vertical-align:top;padding:10px 0px;text-align:center;margin:0 auto;\"><img class=\"washcoLogo\" style=\"display:block;height:auto;max-width:100%;width:407px;border:0px;margin:0px auto;padding:0px;text-align:center\" src=\"http://files.constantcontact.com/c403faa7401/feb42e0a-d578-4267-a1cd-840728cf0112.png\"></td></tr></tbody></table></div></td></tr></tbody></table><table class=\"sectionTable bottomBanner\" style=\"width:100%;min-width:100%;padding:0px;border-spacing:0px\"><tbody><tr><td class=\"sectionCell bottomBanner\" style=\"background-color:#cf8a00;width:100%;text-align:center;vertical-align:top\"></td></tr></tbody></table><table class=\"sectionTable addressInfo\" style=\"width:100%;min-width:100%;padding:0px;border-spacing:0px\"><tbody><tr><td class=\"sectionCell addressInfo\" style=\"width:100%; color:#080c85\"><h3 class=\"addressOne\" style=\"font-size:12px;font-weight:bold;margin:10px 0px 0px;mso-line-height-rule:exactly\">100 W. Washington Street, Suite 1401</h3><h3 class=\"addressTwo\" style=\"font-size:12px;font-weight:bold;margin:0px 0px 10px;mso-line-height-rule:exactly\">Hagerstown, MD 21740 <span style=\"color:#d18a17;font-weight:bold\">|</span> 240.313.2380</h3></td></tr></tbody></table><table class=\"sectionTable emailTitle\" style=\"width:100%;min-width:100%;padding:0px;border-spacing:0px\"><tbody><tr><td class=\"sectionCell emailTitle\" style=\"width:100%;text-align:center;vertical-align:top\"><h2 style=\"font-weight:bold;text-align:center;font-size:16px;font-family:Arial,Verdana,Helvetica,sans-serif;color:#000000;margin:0;mso-line-height-rule:exactly;\">New Essential Personnel Job Postings</h2></td></tr></tbody></table><table class=\"sectionTable emailBody\" style=\"width:100%;min-width:100%;padding:0px;border-spacing:0px\"><tbody><tr><td class=\"sectionCell emailBody\" style=\"width:100%;text-align:left;vertical-align:top;font-family:Arial,Verdana,Helvetica,sans-serif;font-size:14px;color:#000000;display:block;word-wrap:break-word;line-height:1.2;padding:10px 20px\"><table class=\"sectionCellInner\" style=\"min-width:100%;width:100%;padding:0px;border-spacing:0px;border:0px\"><tbody><tr><td class=\"emailBodyCell\" style=\"font-family:Arial,Verdana,Helvetica,sans-serif;font-size:14px;color:#000000;text-align:left;display:block;word-wrap:break-word;line-height:1.2;padding:10px 20px;vertical-align:top\"><table class=\"emailBodyTable\" style=\"width:100%\"><tr><td class=\"emailBodyContent\" style=\"width:65%\"><p style=\"font-size:14px;font-family:Arial,Verdana,Helvetica,sans-serif\">You are receiving this email because you have subscribed to the [Job Department] Mailing List. If you do not wish to receive these updates, use the link at the bottom of this page to unsubscribe.</p><p style=\"font-size:14px;font-family:Arial,Verdana,Helvetica,sans-serif\">By clicking on the following links, you can access the Employment Opportunities with Washington County Government.</p><p style=\"font-size:14px;font-family:Arial,Verdana,Helvetica,sans-serif\">Positions posted:</p><div class=\"jobsList\">".$jobs."</div></td><td style=\"width:35%\"><img class=\"stateImage\" alt=\"Maryland\" style=\"display:block;height:auto!important;max-width:100%!important;width:205px;border:0px;padding:0px\" src=\"https://files.constantcontact.com/c403faa7401/98f9e61e-66f5-4de8-bf0e-ccc1050b3127.png\"></td></tr></table></td></tr></tbody></table></td></tr></tbody></table><table class=\"sectionTable moreJobs\" style=\"width:100%;min-width:100%;padding:0px;border-spacing:0px\"><tbody><tr><td class=\"sectionCell moreJobs\" style=\"width:100%;vertical-align:top;background-color:#ffffff\"><div><table class=\"sectionCellInnerTable\" style=\"width:100%;min-width:100%;padding:0px;border-spacing:0px;border:0px\"><tbody><tr><td class=\"sectionCellInnerCell\" style=\"font-family:Arial,Verdana,Helvetica,sans-serif;font-size:14px;font-weight:normal;color:#ffffff;text-decoration:none;padding:10px 20px\"><table style=\"width:100%;min-width:100%\"><tbody><tr><td class=\"buttonCell\" style=\"font-family:Arial,Verdana,Helvetica,sans-serif;font-size:14px;font-weight:normal;color:#ffffff;text-decoration:none;padding:0px;vertical-align:top\"><table class=\"buttonTable\" style=\"border:0px;padding:0px;border-spacing:0px;width:initial;border-radius:5px;border-spacing:0px;background-color:#36495f;min-width:initial;margin:0 auto\"><tbody><tr><td class=\"buttonInner\" style=\"font-family:Arial,Verdana,Helvetica,sans-serif;font-size:14px;font-weight:normal;color:#ffffff;text-decoration:none;padding:9px 15px 10px\"><a href=\"https://www.washco-md.net/jobs\" class=\"buttonLink\" style=\"font-weight:bold;font-family:Arial,Verdana,Helvetica,sans-serif;font-size:14px;color:#ffffff;text-decoration:none\" target=\"_blank\">Employment Opportunities</a></td></tr></tbody></table></td></tr></tbody></table></td></tr></tbody></table></div></td></tr></tbody></table><table class=\"sectionTable additionalSubscribe\" style=\"width:100%;min-width:100%;padding:0px;border-spacing:0px\"><tbody><tr><td class=\"sectionCell\" style=\"width:100%;vertical-align:top\"><table class=\"sectionCellInnerTable\" style=\"min-width:100%;width:100%;padding:0px;border-spacing:0px;border:0px\"><tbody><tr><td class=\"additionalSubscribeCell\" style=\"font-family:Arial,Verdana,Helvetica,sans-serif;font-size:14px;color:#000000;text-align:left;display:block;word-wrap:break-word;line-height:1.2;padding:10px 20px;vertical-align:top;\"><p style=\"font-size:14px;font-family:Arial,Verdana,Helvetica,sans-serif\">To subscribe to additional mailing lists, visit our <a href=\"https://www.washco-md.net/pr-marketing/email-notification-sign-up/\" rel=\"noopener noreferrer\" style=\"font-size:14px;font-family:Arial,Verdana,Helvetica,sans-serif;color:#000285;font-weight:normal;font-style:normal;text-decoration:underline\" target=\"_blank\">Subscribe With Us!</a> page. You will receive updates and notifications from Washington County for all mailing lists you subscribe to.</p></td></tr></tbody></table></td></tr></tbody></table><table class=\"sectionTable socialMedia\" style=\"width:100%;min-width:100%;padding:0px;border-spacing:0px\"><tbody><tr><td class=\"sectionCell socialMedia\" style=\"width:100%;vertical-align:top;padding:0px 20px 10px;text-align:center;\"><a href=\"https://www.facebook.com/WashingtonCountyMD/?ref=bookmarks\" class=\"sociallink\" style=\"display:inline-block;text-decoration:none\" target=\"_blank\"><img alt=\"Facebook\" class=\"socialicon\" style=\"width:32px;border:0px;display:inline-block;margin:0;padding:0\" src=\"https://imgssl.constantcontact.com/galileo/images/templates/Galileo-SocialMedia/facebook-visit-default.png\"></a><a href=\"https://twitter.com/WashingtonCoMD\" class=\"sociallink\" style=\"display:inline-block;text-decoration:none\" target=\"_blank\"><img alt=\"Twitter\" class=\"socialicon\" style=\"width:32px;border:0px;display:inline-block;margin:0;padding:0\" src=\"https://imgssl.constantcontact.com/galileo/images/templates/Galileo-SocialMedia/twitter-visit-default.png\"></a><a href=\"https://www.youtube.com/channel/UCTH850WDpvgeJXEI6Bp-nwg?view_as=subscriber\" class=\"sociallink\" style=\"display:inline-block;text-decoration:none\" target=\"_blank\"><img alt=\"YouTube\" class=\"socialicon\" style=\"width:32px;border:0px;display:inline-block;margin:0;padding:0\" src=\"https://imgssl.constantcontact.com/galileo/images/templates/Galileo-SocialMedia/youtube-visit-default.png\"></a><a href=\"https://www.instagram.com/WashingtonCoMD/\" class=\"sociallink\" style=\"display:inline-block;text-decoration:none\" target=\"_blank\"><img alt=\"Instagram\" class=\"socialicon\" style=\"width:32px;border:0px;display:inline-block;margin:0;padding:0\" src=\"https://imgssl.constantcontact.com/galileo/images/templates/Galileo-SocialMedia/instagram-visit-default.png\"></a></td></tr></tbody></table><table class=\"sectionTable footerBanner\" style=\"width:100%;min-width:100%;padding:0px;border-spacing:0px\"><tbody><tr><td class=\"sectionCell footerBanner\" style=\"background-color:#cf8a00;width:100%;text-align:center;vertical-align:top\">&nbsp;</td></tr></tbody></table></div></td></tr></tbody></table></td></tr></tbody></table></td></tr></tbody></table></body></html>";
    $emailData = array(
        "name" => $name,
        "subject" => "",
        "from_name" => "From your Government",
        "from_email" => "pr@something.gov",
        "reply_to_email" => "pr@something.gov",
        "status" => "SCHEDULED",
    	"is_permission_reminder_enabled" => false,    
    	"permission_reminder_text" => "Hi, just a reminder that you\'re receiving this email because you have expressed an interest in MyCompany. Don\'t forget to add from_email@example.com to your address book so we\'ll be sure to land in your inbox! You may unsubscribe if you no longer wish to receive our emails.",
        "is_view_as_webpage_enabled" => false,    
    	"view_as_web_page_text" => "",
        "view_as_web_page_link_text" => "",
        "greeting_salutations" => "Hello",
        "greeting_name" => "FIRST_NAME",
        "greeting_string" => "Dear ",
        "email_content" => $message,
        "text_content" => "",
        "email_content_format" => "HTML",
        "style_sheet" => "",
        "sent_to_contact_lists" => [
            json_encode(array("id"=>"1111111111"))
        ]
    );
    $ch = curl_init( 'https://api.constantcontact.com/v2/emailmarketing/campaigns?api_key=' ); //add your api key
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $bearer_token,
        'Accept: application/json'
        ));
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    $result = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return array($http_status, json_decode($result));
}

function sendCampaign($bearer_token, $campaignId) {
    $schedule_time = date('Y-m-d\TH:i:s.Z\Z', strtotime("+30 minutes"));
    $schedule = array("scheduled_date" => $schedule_time);
    $ch = curl_init( 'https://api.constantcontact.com/v2/emailmarketing/campaigns/'.$campaignId.'/schedules?api_key=' ); //add your api key
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($schedule));
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $bearer_token,
        'Accept: application/json'
        ));
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    $result = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return array($http_status, json_decode($result));
}
