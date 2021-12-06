<?php

declare(strict_types=1);

/**
 * @author Gregor Mitzka <gregor.mitzka@gmail.com>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Service\Avatar;

use Exception;
use Horde_Mail_Rfc822_Address;
use OCP\Files\IMimeTypeDetector;
use OCP\Http\Client\IClientService;

class BimiSource implements IAvatarSource {

  /** @var IClientService */
  private $clientService;

  /** @var IMimeTypeDetector */
  private $mimeDetector;

  public function __construct(
    IClientService $clientService,
    IMimeTypeDetector $mimeDetector
  ) {
    $this->clientService = $clientService;
    $this->mimeDetector = $mimeDetector;
  }

  /**
   * Does this source query external services?
   *
   * @return bool
   */
  public function isExternal(): bool {
    return true;
  }

  /**
   * @param string $email_address sender email address
   * @param AvatarFactory $factory
   * @return Avatar|null avatar URL if one can be found
   */
  public function fetch(
    string $email_address,
    AvatarFactory $factory
  ): ?Avatar {
    $horde = new Horde_Mail_Rfc822_Address( $email_address );
    // TODO: add support for other selectors
    $domain = 'default._bimi.' . $horde->host;

    $iconUrl = $this->getIconUrl( $domain );

    if ( is_null( $iconUrl ) ) {
      return null;
    }

    /** @var string $iconUrl */
    $client = $this->clientService->newClient();

    try {
      $response = $client->get( $iconUrl );
    } catch ( Exception $exception ) {
      return null;
    }

    // Don't save 0 byte images
    $body = $response->getBody();

    if ( strlen( $body ) === 0 ) {
      return null;
    }

    $mime = $this->mimeDetector->detectString( $body );

    return $factory->createExternal(
      $iconUrl,
      $mime
    );
  }

  /**
   * @param string $domain domain of the BIMI record
   * @return string|null BIMI record if found
   */
  private function getBimiRecord( $domain ): ?string {
    $records = dns_get_record(
      $domain,
      DNS_TXT
    );

    foreach ( $records as $record ) {
      if ( strpos( $record['txt'], 'v=BIMI1;' ) === 0 ) {
        return $record['txt'];
      }
    }

    return null;
  }

  /**
   * @param string $string
   * @return array
   */
  private function getDnsTags( $string ): array {
    $fragments = explode( ';', $string );
    $tags = [];

    foreach ( $fragments as $fragment ) {
      $pair = explode( '=', $fragment, 2 );

      if ( count( $pair ) === 1 ) {
        continue;
      }

      $name = trim( $pair[0] );
      $value = trim( $pair[1] );

      if ( !empty( $value ) ) {
        $tags[ $name ] = trim( $value );
      }
    }

    return $tags;
  }

  /**
   * @param string $domain domain of the BIMI record
   * @return string|null URL to the brand logo if found
   */
  private function getIconUrl( $domain ): ?string {
    $dns_record = $this->getBimiRecord( $domain );

    if ( is_null( $dns_record ) ) {
      return null;
    }

    $tags = $this->getDnsTags( $dns_record );

    if ( empty( $tags ) || !isset( $tags['l'] ) ) {
      return null;
    }

    return $tags['l'];
  }
}
