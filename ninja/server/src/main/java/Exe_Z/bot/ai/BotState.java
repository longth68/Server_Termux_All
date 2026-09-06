package Exe_Z.bot.ai;

/**
 * Port từ NRO VirtualState: máy trạng thái BOT.
 */
public enum BotState {
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
    OFFLINE,
    WANDER
}
