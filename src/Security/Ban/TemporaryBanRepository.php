<?php

declare(strict_types=1);

namespace VictorWitkamp\OpenWPSecurity\Core\Security\Ban;

interface TemporaryBanRepository {
	public function find_active_temporary_ban( string $ip_address ): ?TemporaryBan;

	public function get_active_temporary_bans(): array;

	public function count_active_temporary_bans(): int;

	public function save_temporary_ban( TemporaryBan $temporary_ban ): bool;

	public function remove_temporary_ban( string $ip_address ): bool;

	public function clear_temporary_bans(): int;

	public function purge_expired_temporary_bans(): int;
}
