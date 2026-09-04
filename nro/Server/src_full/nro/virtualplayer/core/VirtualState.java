package nro.virtualplayer.core;

/**
 * Trạng thái hiện tại của Virtual Player.
 * PHASE 2 - Virtual Player Core.
 */
public enum VirtualState {
    SPAWN,
    IDLE,
    FIND_TARGET,
    MOVE_TO_TARGET,
    ATTACK,
    ESCAPE,
    HEAL,
    PICK_ITEM,
    GO_SHOP,
    DO_QUEST,
    EXPLORE,
    REST,
    SOCIAL,
    DEAD,
    RESPAWN,
    CHANGE_MAP,
    OFFLINE
}
