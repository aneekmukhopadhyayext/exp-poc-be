<?php

/**
 * @file
 * Contains the setup script for the Next module.
 */

declare(strict_types=1);

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Helper\Table;
use Drupal\Core\File\FileSystemInterface;

// Create User, consumers and oAuth keys.
$role = 'decoupled_api';
$user_name = 'decoupled_api_user';
$user_password = \Drupal::service('password_generator')->generate();

try {
  // Generate the OAuth key.
  $keygen = \Drupal::service('simple_oauth.key.generator');
  $file_system = \Drupal::service('file_system');
  $keypath = '/var/www/html/ssh-keys';
  // If the directory does not exist, create it.
  if (!is_dir($keypath)) {
    $file_system->prepareDirectory($keypath, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    echo "Created directory: $keypath\n";
  }
  // Check if the directory exists, and have private.key and public.key files.
  if (
    !file_exists($keypath . '/private.key') ||
    !file_exists($keypath . '/public.key')
  ) {
    $keygen->generateKeys($keypath);
    echo "Created OAuth keys at: $keypath\n";
  }

  $user_storage = \Drupal::entityTypeManager()->getStorage('user');

  /** @var \Drupal\user\Entity\UserInterface $user */
  $user = $user_storage->loadByProperties(['name' => $user_name]);
  if (!$user) {
    $user = $user_storage->create([
      'name' => $user_name,
      'mail' => "$user_name@example.com",
      'pass' => $user_password,
      'status' => TRUE,
    ]);
    $user->addRole($role);
    $user->save();


    // Once the user is created, create a consumer for the user.
    $consumer_storage = \Drupal::entityTypeManager()->getStorage('consumer');
    $client_id = \Drupal::service('uuid')->generate();
    $client_secret = \Drupal::service('uuid')->generate();
    $consumer = $consumer_storage->create([
      'label' => 'Decoupled API Consumer',
      'client_id' => $client_id,
      'secret' => $client_secret,
      'third_party' => 1,
      'confidential' => 1,
      'roles' => $role,
      'grant_types' => ['client_credentials'],
      'scopes' => ['decoupled_api_scope'],
      'user_id' => $user->id(),
      'access_token_expiration' => 7200,

    ]);
    $consumer->save();
    // Create a table to display the user details in command line.
    $output = new ConsoleOutput();
    $table = new Table($output);
    $table->setHeaders(['Username', 'Password', 'Role', 'Client ID', 'Client Secret']);
    $table->setRows([[$user_name, $user_password, $role, $client_id, $client_secret]]);
    $table->render();
  }
  else {
    echo "User '$user_name' already exists. Skipping user creation.\n";
  }
}
catch (\Exception $e) {
  \Drupal::logger('next_setup')->error($e->getMessage());
  return NULL;
}
