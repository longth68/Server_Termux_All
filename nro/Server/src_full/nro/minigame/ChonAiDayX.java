package nro.minigame;

import java.util.ArrayList;
import java.util.Comparator;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import nro.player.Player;
import nro.server.ServerManager;
import nro.services.ChatGlobalService;
import nro.services.Service;
import Utils.Util;

/**
 * Chọn Ai Đây bản Ngọc Xanh (Gem) & Hồng Ngọc (Ruby) - chuyển từ SuperVIP-SCR-ByBen.
 * Mỗi 5 phút chọn ngẫu nhiên 1 người may mắn theo tỷ lệ đóng góp (top 5),
 * nhận 90% tổng giải. Bản thường/VIP như bản Gold gốc.
 */
public class ChonAiDayX implements Runnable {

    public static final byte CURRENCY_GEM = 0;
    public static final byte CURRENCY_RUBY = 1;

    public long normar;
    public long vip;
    public long lastTimeEnd = System.currentTimeMillis();

    private final Map<Player, Long> playersNormar = new HashMap<>();
    private final Map<Player, Long> playersVIP = new HashMap<>();

    private final byte currency;
    private static ChonAiDayX gemInstance;
    private static ChonAiDayX rubyInstance;

    private ChonAiDayX(byte currency) {
        this.currency = currency;
        this.lastTimeEnd = System.currentTimeMillis() + 300000;
    }

    public static ChonAiDayX gI(byte currency) {
        if (currency == CURRENCY_GEM) {
            if (gemInstance == null) {
                gemInstance = new ChonAiDayX(CURRENCY_GEM);
            }
            return gemInstance;
        }
        if (rubyInstance == null) {
            rubyInstance = new ChonAiDayX(CURRENCY_RUBY);
        }
        return rubyInstance;
    }

    public String currencyName() {
        return currency == CURRENCY_GEM ? "Ngọc Xanh" : "Hồng Ngọc";
    }

    // ===== TRUY VẤN CƯỢC =====
    public long getBetNormar(Player pl) {
        Long v = playersNormar.get(pl);
        return v == null ? 0 : v;
    }

    public long getBetVip(Player pl) {
        Long v = playersVIP.get(pl);
        return v == null ? 0 : v;
    }

    /**
     * % cơ hội trúng = tỷ lệ đóng góp trong giải (như player.percentGold của bản vàng).
     */
    public int percent(Player pl, boolean vipPot) {
        long my = vipPot ? getBetVip(pl) : getBetNormar(pl);
        long total = vipPot ? this.vip : this.normar;
        if (total <= 0 || my <= 0) {
            return 0;
        }
        double percent = ((double) my / total) * 100;
        if (percent > 100) {
            percent = 100;
        }
        return (int) Math.ceil(percent);
    }

    // ===== ĐẶT CƯỢC =====
    public void addBet(Player pl, boolean vipPot, long amount) {
        if (pl == null || amount <= 0) return;
        if (!isAllowBetting()) {
            Service.gI().sendThongBao(pl, "Đã hết thời gian đặt cược, vui lòng đợi giải sau!");
            return;
        }
        if (vipPot) {
            playersVIP.put(pl, getBetVip(pl) + amount);
            this.vip += amount;
        } else {
            playersNormar.put(pl, getBetNormar(pl) + amount);
            this.normar += amount;
        }
        Service.gI().sendThongBao(pl, "Đặt giải " + (vipPot ? "VIP" : "thường") + " ("
                + currencyName() + "): " + Util.format(amount));
    }

    public boolean isAllowBetting() {
        long remain = lastTimeEnd - System.currentTimeMillis();
        return remain > 10000;
    }

    // ===== CHU TRÌNH GAME (theo đúng ChonAiDay bản gold hiện có) =====
    @Override
    public void run() {
        while (ServerManager.isRunning) {
            try {
                Thread.sleep(1000);
                long timeToEnd = (lastTimeEnd - System.currentTimeMillis()) / 1000;
                if (timeToEnd <= 0) {
                    try {
                        processNormar();
                        processVip();
                    } catch (Exception e) {
                        e.printStackTrace();
                    } finally {
                        reset();
                    }
                }
            } catch (InterruptedException e) {
                break;
            } catch (Exception e) {
                e.printStackTrace();
            }
        }
    }

    private List<Player> topContributors(Map<Player, Long> pool, long total) {
        List<Player> sorted = new ArrayList<>();
        for (Player p : pool.keySet()) {
            sorted.add(p);
        }
        sorted.sort(Comparator.comparing(
                p -> Math.ceil(((double) safeGet(pool, p) / Math.max(1, total)) * 100),
                Comparator.reverseOrder()));
        if (sorted.size() > 5) {
            return sorted.subList(0, 5);
        }
        return sorted;
    }

    private long safeGet(Map<Player, Long> pool, Player p) {
        Long v = pool.get(p);
        return v == null ? 0 : v;
    }

    private void processNormar() {
        if (playersNormar.isEmpty() || normar <= 0) return;
        long reward = normar * 90 / 100; // trừ 10% VAT
        List<Player> top = topContributors(playersNormar, normar);
        if (top.isEmpty()) return;
        Player winner = top.get(Util.nextInt(0, top.size() - 1));
        payWinner(winner, reward, false);
    }

    private void processVip() {
        if (playersVIP.isEmpty() || vip <= 0) return;
        long reward = vip * 90 / 100;
        List<Player> top = topContributors(playersVIP, vip);
        if (top.isEmpty()) return;
        Player winner = top.get(Util.nextInt(0, top.size() - 1));
        payWinner(winner, reward, true);
    }

    private void payWinner(Player pl, long reward, boolean vipPot) {
        try {
            if (nro.server.Client.gI().getPlayerByName(pl.name) == null) {
                ChatGlobalService.gI().chat(pl, "Người chơi " + pl.name
                        + " đã trúng giải Chọn Ai Đây " + currencyName()
                        + (vipPot ? " VIP" : "") + ": " + Util.format(reward) + " " + currencyName()
                        + " (không nhận được do offline)!");
                return;
            }
            if (currency == CURRENCY_GEM) {
                pl.inventory.gem += (int) reward;
            } else {
                pl.inventory.ruby += (int) reward;
            }
            Service.gI().sendMoney(pl);
            Service.gI().sendThongBaoFromAdmin(pl,
                    "|8|[ Chọn Ai Đây " + ServerManager.NAME + " ]\n\n|7|Chúc mừng bạn đã trúng giải "
                    + (vipPot ? "VIP " : "") + currencyName() + "!\n|1|Số tiền nhận được:\n|3|"
                    + Util.format(reward) + " " + currencyName());
            ChatGlobalService.gI().chat(pl, "Chúc mừng " + pl.name + " trúng giải Chọn Ai Đây "
                    + currencyName() + (vipPot ? " VIP" : "") + ": " + Util.format(reward) + " " + currencyName() + "!");
        } catch (Exception ignored) {}
    }

    private void reset() {
        playersNormar.clear();
        playersVIP.clear();
        normar = 0;
        vip = 0;
        lastTimeEnd = System.currentTimeMillis() + 300000;
    }
}
