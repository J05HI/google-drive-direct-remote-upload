<?php

/**
 * Made with love by J05HI [https://github.com/J05HI]
 * Released under the MIT.
 *
 * Feel free to contribute!
 */

class GoogleApi {
    
    private $applicationName = __DIR__ . 'Direct-Loader';
    private $credentialsPath = __DIR__ . '/credentials/auth-credentials.json';
    private $clientSecretPath = __DIR__ . '/credentials/oauth-credentials.json';
    # Change to "Google_Service_Drive::DRIVE_FILE" later
    private $scopes = [Google_Service_Drive::DRIVE];
    
    /**
     * Get the authorized Google API client.
     *
     * @return Google_Client the authorized client object
     * @throws Google_Exception
     */
    public function getClient(): \Google_Client {
        $client = new Google_Client();
        $client->setApplicationName($this->applicationName);
        $client->setScopes($this->scopes);
        $client->setAuthConfig($this->clientSecretPath);
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');
        
        # Load previously authorized credentials from a file.
        if (file_exists($this->credentialsPath)) {
            $accessToken = json_decode(file_get_contents($this->credentialsPath), true);
        } else {
            # Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));
            
            # Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            
            # Store the credentials to disk.
            if (!file_exists(dirname($this->credentialsPath))) {
                mkdir(dirname($this->credentialsPath), 0700, true);
            }
            
            file_put_contents($this->credentialsPath, json_encode($accessToken));
            printf("Credentials saved to %s\n", $this->credentialsPath);
        }
        $client->setAccessToken($accessToken);
        
        # Refresh the token if it's expired.
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($this->credentialsPath, json_encode($client->getAccessToken()));
        }
        
        return $client;
    }
}