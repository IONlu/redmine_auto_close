<?php

require_once __DIR__.'/config.php';

const CLOSE_BODY = array(
    'issue' => array(
        'status_id' => Config::STATUS_CLOSED,
    ),
);

try {
    $issuesToClose = getResolvedIssues();
    print_r($issuesToClose);

    foreach ($issuesToClose as $issueToClose) {
        print_r($issueToClose);

        print_r(Config::REDMINE_URL.'/issues/'.$issueToClose['id'].'.json'."\n");

        print_r(CLOSE_BODY);

        die();
    }

    die("done\n");
} catch (Exception $e) {
    echo $e->getMessage();
}

function getResolvedIssues()
{
    return getAllRows(
        Config::REDMINE_URL
            .'/issues.json'
            .'?status_id='.Config::STATUS_RESOLVED
            .'&updated_on=%3C%3D'.date('Y-m-d\TH:i:s\Z', strtotime('-'.Config::CLOSE_IF_OLDER_THEN))
    );
}

function callApi($url)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL            => $url,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HTTPHEADER     => array(
            'x-redmine-api-key: '.Config::API_KEY,
        ),
    ));

    $response = curl_exec($curl);
    $err      = curl_error($curl);

    curl_close($curl);

    if ($err) {
        throw new Exception('cURL Error #:'.$err);
    }

    return json_decode($response, true);
}

function getAllRows($url, $offset = 0, $limit = 100)
{
    $results = callApi($url.'&offset='.$offset.'&limit='.$limit);

    if ($results['offset'] + $results['limit'] < $results['total_count']) {
        $issues = array_merge($results['issues'], getAllRows($url, $offset + $limit, $limit));
    } else {
        $issues = $results['issues'];
    }

    return $issues;
}

function getLatestIssues($startDate = null)
{
    if ($startDate === null) {
        $startDate = date('Y-m-d\TH:i:s\Z', strtotime('-'.Config::TIME_WINDOW));
    }

    $latestIssues = getAllRows(Config::REDMINE_URL.'/issues.json?updated_on=>='.$startDate.'&sort=updated_on:desc');

    return $latestIssues;
}
