<?php

date_default_timezone_set('Europe/Luxembourg');
require_once __DIR__.'/config.php';

const CLOSE_BODY = array(
    'issue' => array(
        'status_id' => Config::STATUS_CLOSED,
        'notes'     => "Hey o/\n\nsorry to bother you, but I'm closing this issue now as it has been resolved for over ".Config::CLOSE_IF_OLDER_THEN." ago.\n\nRegards,\nVaiva",
    ),
);

try {
    $issuesToClose = getResolvedIssues();
    foreach ($issuesToClose as $issueToClose) {
        $url = Config::REDMINE_URL.'/issues/'.$issueToClose['id'].'.json';
        callApi($url, 'PUT', CLOSE_BODY);
    }
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

function callApi($url, $method = 'GET', $data = null)
{
    $curl = curl_init();

    $curlOptions = array(
        CURLOPT_URL            => $url,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        CURLOPT_HTTPHEADER     => array(
            'content-type: application/json',
            'x-redmine-api-key: '.Config::API_KEY,
        ),
    );

    if ($data) {
        $curlOptions = $curlOptions + array(
            CURLOPT_POSTFIELDS => json_encode(CLOSE_BODY),
        );
    }

    curl_setopt_array($curl, $curlOptions);

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
