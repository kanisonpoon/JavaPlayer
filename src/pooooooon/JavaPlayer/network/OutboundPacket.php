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

abstract class OutboundPacket extends Packet
{
	//Status

	//Login
	const LOGIN_DISCONNECT_PACKET = 0x00;
	const ENCRYPTION_REQUEST_PACKET = 0x01;
	const LOGIN_SUCCESS_PACKET = 0x02;
	
	const SPAWN_ENTITY_PACKET = 0x00;
	const SPAWN_EXPERIENCE_ORB_PACKET = 0x01;
	const SPAWN_PLAYER_PACKET = 0x02;
	const ENTITY_ANIMATION_PACKET = 0x03;
	const STATISTICS_PACKET = 0x04;
	const ACKNOWLEDGE_PLAYER_DIGGING_PACKET = 0x05;
	const BLOCK_BREAK_ANIMATION_PACKET = 0x06;
	const BLOCK_ENTITY_DATA_PACKET = 0x07;
	const BLOCK_ACTION_PACKET = 0x08;
	const BLOCK_CHANGE_PACKET = 0x09;
	const BOSS_BAR_PACKET = 0x0A;
	const SERVER_DIFFICULTY_PACKET = 0x0B;
	const CHAT_MESSAGE_PACKET = 0x0C;
	const CLEAR_TITLES_PACKET = 0x0D;
	const TAB_COMPLETE_PACKET = 0x0E;
	const DECLARE_COMMANDS_PACKET = 0x0F;
	const CLOSE_WINDOW_PACKET = 0x10;
	const WINDOW_ITEMS_PACKET = 0x11;
	const WINDOW_PROPERTY_PACKET = 0x12;
	const SET_SLOT_PACKET = 0x13;
	const SET_COOLDOWN_PACKET = 0x14;
	const PLUGIN_MESSAGE_PACKET = 0x15;
	const NAMED_SOUND_EFFECT_PACKET = 0x16;
	const DISCONNECT_PACKET = 0x17;
	const ENTITY_STATUS_PACKET = 0x18;
	const EXPLOSION_PACKET = 0x19;
	const UNLOAD_CHUNK_PACKET = 0x1A;
	const CHANGE_GAME_STATE_PACKET = 0x1B;
	const OPEN_HORSE_WINDOW_PACKET = 0x1C;
	const INITIALIZE_WORLD_BORDER_PACKET = 0x1D;
	const KEEP_ALIVE_PACKET = 0x1E;
	const CHUNK_DATA_PACKET = 0x1F;
	const EFFECT_PACKET = 0x20;
	const PARTICLE_PACKET = 0x21;
	const UPDATE_LIGHT_PACKET = 0x22;
	const JOIN_GAME_PACKET = 0x23;
	const MAP_DATA_PACKET = 0x24;
	const TRADE_LIST_PACKET = 0x25;
	const ENTITY_POSITION_PACKET = 0x26;
	const ENTITY_POSITION_AND_ROTATION_PACKET = 0x27;
	const ENTITY_ROTATION_PACKET = 0x28;
	const VEHICLE_MOVE_PACKET = 0x29;
	const OPEN_BOOK_PACKET = 0x2A;
	const OPEN_WINDOW_PACKET = 0x2B;
	const OPEN_SIGN_EDITOR_PACKET = 0x2C;
	const PING_PACKET = 0x2D;
	const CRAFT_RECIPE_RESPONSE_PACKET = 0x2E;
	const PLAYER_ABILITIES_PACKET = 0x2F;
	const PLAYER_CHAT_MESSAGE_PACKET = 0x30; //* IDK I just add
	const END_COMBAT_EVENT_PACKET = 0x31;
	const ENTER_COMBAT_EVENT_PACKET = 0x32;
	const DEATH_COMBAT_EVENT_PACKET = 0x33;
	const PLAYER_INFO_PACKET = 0x34;
	const FACE_PLAYER_PACKET = 0x35;
	const PLAYER_POSITION_AND_LOOK_PACKET = 0x36;
	const UNLOCK_RECIPES_PACKET = 0x37;
	const DESTROY_ENTITIES_PACKET = 0x38;
	const REMOVE_ENTITY_EFFECT_PACKET = 0x39;
	const RESOURCE_PACK_SEND_PACKET = 0x3A;
	const RESPAWN_PACKET = 0x3B;
	const ENTITY_HEAD_LOOK_PACKET = 0x3C;
	const MULTI_BLOCK_CHANGE_PACKET = 0x3D;
	const SERVER_DATA_PACKET = 0x3F; //* I did not see Sever Dat and i add this :-;
	//const SELECT_ADVANCEMENT_TAB_PACKET = 0x40; (I think it not have!!) 
	const ACTION_BAR_PACKET = 0x40;
	const WORLD_BORDER_CENTER_PACKET = 0x41;
	const WORLD_BORDER_LERP_SIZE_PACKET = 0x42;
	const WORLD_BORDER_SIZE_PACKET = 0x43;
	const WORLD_BORDER_WARNING_DELAY_PACKET = 0x44;
	const WORLD_BORDER_WARNING_REACH_PACKET = 0x45;
	const CAMERA_PACKET = 0x46;
	const HELD_ITEM_CHANGE_PACKET = 0x47;
	const UPDATE_VIEW_POSITION_PACKET = 0x48;
	const UPDATE_VIEW_DISTANCE_PACKET = 0x49; // Not used by the dedicated server (Huh?)
	const SPAWN_POSITION_PACKET = 0x4A;
	const SET_DISPLAY_CHAT_PREVIEW_PACKET = 0x4B;
	const DISPLAY_SCOREBOARD_PACKET = 0x4C;
	const ENTITY_METADATA_PACKET = 0x4D;
	const ATTACH_ENTITY_PACKET = 0x4E;
	const ENTITY_VELOCITY_PACKET = 0x4F;
	const ENTITY_EQUIPMENT_PACKET = 0x50;
	const SET_EXPERIENCE_PACKET = 0x51;
	const UPDATE_HEALTH_PACKET = 0x52;
	const SCOREBOARD_OBJECTIVE_PACKET = 0x53;
	const SET_PASSENGERS_PACKET = 0x54;
	const TEAMS_PACKET = 0x55;
	const UPDATE_SCORE_PACKET = 0x56;
	const SET_SIMULATION_DISTANCE_PACKET = 0x57;
	const SET_TITLE_SUBTITLE_PACKET = 0x58;
	const TIME_UPDATE_PACKET = 0x59;
	const SET_TITLE_TEXT_PACKET = 0x5A;
	const SET_TITLE_TIME_PACKET = 0x5B;
	const ENTITY_SOUND_EFFECT_PACKET = 0x5C;
	const SOUND_EFFECT_PACKET = 0x5D;
	const STOP_SOUND_PACKET = 0x5E;
	const SYSTEM_CHAT_MESSAGE_PACKET = 0x5F;
	const PLAYER_LIST_HEADER_AND_FOOTER_PACKET = 0x60;
	const NBT_QUERY_RESPONSE_PACKET = 0x61;
	const COLLECT_ITEM_PACKET = 0x62;
	const ENTITY_TELEPORT_PACKET = 0x63;
	const ADVANCEMENTS_PACKET = 0x63;
	const ENTITY_PROPERTIES_PACKET = 0x65;
	const ENTITY_EFFECT_PACKET = 0x66;
	const DECLARE_RECIPES_PACKET = 0x66;
	const TAGS_PACKET = 0x67;

	/**
	 * @throws ErrorException
	 * @deprecated
	 */
	protected final function decode(): void
	{
		throw new ErrorException(get_class($this) . " is subclass of OutboundPacket: don't call decode() method");
	}

}
