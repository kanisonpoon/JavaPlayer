<?php
/**
 *  ______  __         ______               __    __
 * |   __ \|__|.-----.|   __ \.----..-----.|  |_ |  |--..-----..----.
 * |   __ <|  ||  _  ||   __ <|   _||  _  ||   _||     ||  -__||   _|
 * |______/|__||___  ||______/|__|  |_____||____||__|__||_____||__|
 *             |_____|
 *
 * BigBrother plugin for PocketMine-MP
 * Copyright (C) 2014-2015 shoghicp <https://github.com/shoghicp/BigBrother>
 * Copyright (C) 2016- BigBrotherTeam
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author BigBrotherTeam
 * @link   https://github.com/BigBrotherTeam/BigBrother
 *
 */

declare(strict_types=1);

namespace pooooooon\javaplayer\network;

use ErrorException;

abstract class InboundPacket extends Packet
{
	//Play
	const TELEPORT_CONFIRM_PACKET = 0x00;
	const QUERY_BLOCK_NBT_PACKET = 0x01;
	const SET_DIFFICULTY_PACKET = 0x02;
	const CHAT_MESSAGE_PACKET = 0x03;
	const CLIENT_STATUS_PACKET = 0x04;
	const CLIENT_SETTINGS_PACKET = 0x05;
	const TAB_COMPLETE_PACKET = 0x06;
	const WINDOW_CONFIRMATION_PACKET = 0x07;
	const CLICK_WINDOW_BUTTON_PACKET = 0x08;
	const CLICK_WINDOW_PACKET = 0x08;
	const CLOSE_WINDOW_PACKET = 0x09;
	const PLUGIN_MESSAGE_PACKET = 0x0a;
	const EDIT_BOOK_PACKET = 0x0b;
	const QUERY_ENTITY_NBT_PACKET = 0x0c;
	const INTERACT_ENTITY_PACKET = 0x0d;
	const GENERATE_STRUCTURE_PACKET = 0x0e;
	const KEEP_ALIVE_PACKET = 0x0f;
	const LOCK_DIFFICULTY_PACKET = 0x10;
	const PLAYER_POSITION_PACKET = 0x11;
	const PLAYER_POSITION_AND_ROTATION_PACKET = 0x12;
	const PLAYER_ROTATION_PACKET = 0x13;
	const PLAYER_MOVEMENT_PACKET = 0x14;
	const VEHICLE_MOVE_PACKET = 0x15;
	const STEER_BOAT_PACKET = 0x16;
	const PICK_ITEM_PACKET = 0x17;
	const CRAFT_RECIPE_REQUEST_PACKET = 0x18;
	const PLAYER_ABILITIES_PACKET = 0x19;
	const PLAYER_DIGGING_PACKET = 0x1a;
	const ENTITY_ACTION_PACKET = 0x1b;
	const STEER_VEHICLE_PACKET = 0x1c;
	const SET_DISPLAYED_RECIPE_PACKET = 0x1e;
	const SET_RECIPE_BOOK_STATE_PACKET = 0x1f;
	const NAME_ITEM_PACKET = 0x20;
	const RESOURCE_PACK_STATUS_PACKET = 0x21;
	const ADVANCEMENT_TAB_PACKET = 0x22;
	const SELECT_TRADE_PACKET = 0x23;
	const SET_BEACON_EFFECT_PACKET = 0x24;
	const HELD_ITEM_CHANGE_PACKET = 0x25;
	const UPDATE_COMMAND_BLOCK_PACKET = 0x26;
	const UPDATE_COMMAND_BLOCK_MINECRAFT_PACKET = 0x27;
	const UPDATE_JIGSAW_BLOCK_PACKET = 0x28;
	const CREATIVE_INVENTORY_ACTION_PACKET = 0x28;
	//29...?
	const UPDATE_STRUCTURE_BLOCK_PACKET = 0x2a;
	const UPDATE_SIGN_PACKET = 0x2b;
	const ANIMATION_PACKET = 0x2c;
	const SPECTATE_PACKET = 0x2d;
	const PLAYER_BLOCK_PLACEMENT_PACKET = 0x2e;
	const USE_ITEM_PACKET = 0x2f;

	//Status

	//Login
	const LOGIN_START_PACKET = 0x00;
	const ENCRYPTION_RESPONSE_PACKET = 0x01;

	/**
	 * @throws ErrorException
	 * @deprecated
	 */
	protected final function encode(): void
	{
		throw new ErrorException(get_class($this) . " is subclass of InboundPacket: don't call encode() method");
	}

}
