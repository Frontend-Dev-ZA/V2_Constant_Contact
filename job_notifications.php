<?php
/*
Plugin Name: Job Notifications for Contstant Contact using API V2
Plugin URI: https://semeonline.co.za
Plugin Platform: Wordpress - Version: Stable release: 5.4.2
Plugin Date: July 2020 - "The Year of Covid-19"

Description: Automate sending of emails to users who have subscribed for a job post
Author: Nilesh Cara
Company URI: https://semeonline.co.za
Version: 1.1
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
    $send_to_list[] = (object)array("id"=>"");
    $message = "<!DOCTYPE html><html></html>"; //Don't forget your email template
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
