package Exe_Z.bot.ai;

import Exe_Z.bot.AutoFarmBot;
import Exe_Z.map.item.ItemMap;
import Exe_Z.mob.Mob;
import Exe_Z.model.Char;
import Exe_Z.util.Log;

/**
 * Port từ NRO VirtualBrain: vòng lặp quyết định theo BotState.
 * Được gọi từ AutoFarmBot.updateEveryHalfSecond() khi aiEnabled=true.
 */
public class BotBrain {

    public static void update(AutoFarmBot bot) {
        if (bot == null || bot.zone == null || bot.isCleaned) {
            return;
        }
        try {
            bot.botNeeds.grow();
            if (bot.isDead) {
                bot.botState = BotState.DEAD;
                return;
            }
            // HP thấp -> HEAL ưu tiên
            if (BotCombat.shouldRetreat(bot)) {
                bot.botState = BotState.HEAL;
                BotCombat.heal(bot);
                bot.botNeeds.satisfy(BotNeeds.REST, 5.0);
                return;
            }
            // Làng: chỉ dạo + social nhẹ
            if (bot.aiIsVillage()) {
                bot.botState = BotState.WANDER;
                if (bot.botTick % 6 == 0) {
                    BotMovement.wanderVillage(bot);
                }
                BotChat.tick(bot);
                return;
            }
            Mob target = BotCombat.findTarget(bot);
            // Nhắn tin riêng cho người chơi quen (tự cooldown nội bộ)
            BotChat.tickPrivate(bot);
            BotGoals.ShortTerm want = BotDecision.choose(bot, target);
            bot.botGoals.shortTerm = want;
            switch (want) {
                case HEAL:
                    bot.botState = BotState.HEAL;
                    BotCombat.heal(bot);
                    break;
                case PICK_ITEM: {
                    bot.botState = BotState.PICK_ITEM;
                    ItemMap im = BotPerception.findNearItem(bot, 200);
                    if (im != null) {
                        int d = Exe_Z.util.NinjaUtils.getDistance(bot.x, bot.y, im.getX(), im.getY());
                        if (d <= 20) {
                            bot.aiPickup(im);
                            bot.botNeeds.satisfy(BotNeeds.ITEM, 1.0);
                        } else {
                            BotMovement.moveToward(bot, im.getX(), im.getY());
                        }
                    }
                    break;
                }
                case FIND_MOB:
                    bot.botState = target != null ? BotState.ATTACK : BotState.EXPLORE;
                    if (target != null) {
                        BotCombat.attack(bot, target);
                    } else {
                        maybeChangeMap(bot);
                        BotMovement.wander(bot);
                    }
                    break;
                case PARTY:
                case CHAT:
                    bot.botState = BotState.SOCIAL;
                    BotSocial.tick(bot);
                    BotChat.tick(bot);
                    // Vừa social vừa farm nếu có quái gần
                    if (target != null) {
                        BotCombat.attack(bot, target);
                    } else {
                        BotMovement.wander(bot);
                    }
                    break;
                case CHANGE_MAP:
                    bot.botState = BotState.CHANGE_MAP;
                    maybeChangeMap(bot);
                    BotMovement.wander(bot);
                    break;
                default:
                    bot.botState = BotState.WANDER;
                    BotChat.tick(bot);
                    if (target != null) {
                        bot.botState = BotState.ATTACK;
                        BotCombat.attack(bot, target);
                    } else if (!bot.aiPickupNearest()) {
                        BotMovement.wander(bot);
                    }
                    break;
            }
            // EXPLORE dài hạn: thỉnh thoảng đổi map
            exploreTick(bot);
            // Nâng đồ định kỳ như Anwin (2 phút/lần, có xác suất bỏ qua)
            try {
                BotEquipment.tickUpgrade(bot);
            } catch (Exception ignored) {
            }
        } catch (Exception ex) {
            Log.error("BotBrain err: " + ex.getMessage(), ex);
        }
    }

    private static void exploreTick(AutoFarmBot bot) {
        if (bot == null) {
            return;
        }
        if (!bot.botProfile.personalities.contains(BotPersonality.EXPLORER)) {
            return;
        }
        long now = System.currentTimeMillis();
        if (now - bot.lastAiMapChange < (long) (60000L / Math.max(0.2, BotConfig.MAP_CHANGE_RATE))) {
            return;
        }
        if (BotPerception.realPlayersInZone(bot.zone).isEmpty()
                && Exe_Z.util.NinjaUtils.nextInt(0, 100) < 30) {
            maybeChangeMap(bot);
        }
    }

    /** Đổi map/zone theo level như NRO maybeChangeMap, tôn trọng map làng. */
    public static void maybeChangeMap(AutoFarmBot bot) {
        if (bot == null) {
            return;
        }
        long now = System.currentTimeMillis();
        if (now - bot.lastAiMapChange < 30000L) {
            return;
        }
        bot.lastAiMapChange = now;
        try {
            Exe_Z.map.zones.Zone z = BotMovement.pickZoneByLevel(Math.max(1, bot.level));
            if (z == null || z == bot.zone) {
                return;
            }
            // Không đổi vào map làng nếu đang farm
            int mid = z.map != null ? z.map.id : -1;
            for (int v : new int[]{10, 17, 22, 32, 38, 43, 48, 138, 162}) {
                if (mid == v) {
                    return;
                }
            }
            bot.outZone();
            bot.joinZone(mid, z.id, -1);
            bot.botNeeds.satisfy(BotNeeds.EXPLORE, 3.0);
        } catch (Exception ignored) {
        }
    }

    /** Clan/PvP tick như NRO (NSO: chỉ buff relation, chưa có clan AI). */
    public static void tickClanAndPvp(AutoFarmBot bot) {
        if (bot == null) {
            return;
        }
        Char near = BotPerception.nearestRealPlayer(bot, 200);
        if (near != null && bot.botProfile.personalities.contains(BotPersonality.HELPFUL)) {
            bot.botMemory.adjustRelation(near.name, 1);
        }
    }
}
