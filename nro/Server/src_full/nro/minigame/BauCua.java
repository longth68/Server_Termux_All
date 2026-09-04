package nro.minigame;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;

import nro.inventory.InventoryService;
import nro.player.Player;
import nro.server.Client;
import nro.server.ServerManager;
import nro.services.ChatGlobalService;
import nro.services.Service;
import models.Item.Item;
import models.Item.ItemService;
import Utils.Util;

/**
 * Bầu Cua Tôm Cá - viết mới theo pattern TaiXiu/ChanLe của server.
 * 6 cửa: Bầu, Cua, Tôm, Cá, Gà, Nai. Cược bằng Thỏi Vàng (457).
 * Mỗi viên xí ngầu trúng cửa trả thêm x1 stake (ăn k = nhận stake*(1+k)).
 * Nhà cái nghiêng về cửa có ít tiền cược. Ván 60 giây.
 */
public class BauCua implements Runnable {

    public static final byte BAU = 0;
    public static final byte CUA = 1;
    public static final byte TOM = 2;
    public static final byte CA = 3;
    public static final byte GA = 4;
    public static final byte NAI = 5;

    private static final short THOI_VANG_TEMPLATE = 457;
    private static final long TIME_END = 60000;
    private static final long MIN_BET = 10;
    private static final long MAX_BET_PER_DOOR = 100_000;

    public boolean baotri = false;
    public long lastTimeEnd = System.currentTimeMillis();

    // Tổng cược từng cửa
    public final long[] totals = new long[6];
    // Cược cá nhân từng cửa
    private final Map<Player, Long>[] bets = new HashMap[6];

    {
        for (int i = 0; i < 6; i++) {
            bets[i] = new HashMap<>();
        }
    }

    // Kết quả ván gần nhất (3 viên)
    public int[] lastDice = new int[]{0, 0, 0};
    public final List<String> listKetQua = new ArrayList<>();

    // Ép kết quả (admin) - chỉ hiệu lực 1 ván
    private int[] forceDice = null;

    private static BauCua instance;

    public static BauCua gI() {
        if (instance == null) {
            instance = new BauCua();
        }
        return instance;
    }

    public static String doorName(byte door) {
        switch (door) {
            case BAU: return "Bầu";
            case CUA: return "Cua";
            case TOM: return "Tôm";
            case CA: return "Cá";
            case GA: return "Gà";
            case NAI: return "Nai";
            default: return "?";
        }
    }

    public long getBet(Player pl, byte door) {
        if (pl == null || door < 0 || door > 5) return 0;
        Long v = bets[door].get(pl);
        return v == null ? 0 : v;
    }

    public boolean isAllowBetting() {
        long remain = lastTimeEnd - System.currentTimeMillis();
        return remain > 10000 && remain <= TIME_END && !baotri;
    }

    public StringBuffer getKetQua() {
        StringBuffer kq = new StringBuffer();
        for (String i : listKetQua) {
            kq.append("\n|5|").append(i);
        }
        return kq;
    }

    // ===== ÉP KẾT QUẢ (ADMIN): mảng 3 phần tử 0..5 =====
    public void changeKetQua(int d1, int d2, int d3) {
        forceDice = new int[]{d1, d2, d3};
    }

    // ===== ĐẶT CƯỢC =====
    public void datCuoc(Player pl, byte door, long tienCuoc) {
        if (pl == null || door < 0 || door > 5) return;
        if (baotri) {
            Service.gI().sendThongBao(pl, "Hệ thống đang bảo trì");
            return;
        }
        if (!isAllowBetting()) {
            Service.gI().sendThongBao(pl, "Hết thời gian đặt cược, vui lòng chờ ván sau");
            return;
        }
        if (tienCuoc < MIN_BET) {
            Service.gI().sendThongBao(pl, "Cược tối thiểu " + Util.format(MIN_BET) + " Thỏi Vàng");
            return;
        }
        if (getBet(pl, door) + tienCuoc > MAX_BET_PER_DOOR) {
            Service.gI().sendThongBao(pl, "Tối đa " + Util.format(MAX_BET_PER_DOOR) + " Thỏi Vàng mỗi cửa");
            return;
        }

        Item tv = InventoryService.gI().findItemBag(pl, THOI_VANG_TEMPLATE);
        if (tv == null || tv.quantity < tienCuoc) {
            Service.gI().sendThongBao(pl, "Không đủ Thỏi Vàng");
            return;
        }

        InventoryService.gI().subQuantityItemsBag(pl, tv, (int) tienCuoc);
        InventoryService.gI().sendItemBag(pl);

        bets[door].put(pl, getBet(pl, door) + tienCuoc);
        totals[door] += tienCuoc;
        Service.gI().sendThongBao(pl, "Đặt " + doorName(door) + ": " + Util.format(tienCuoc) + " Thỏi Vàng");
    }

    // ===== CHU TRÌNH GAME =====
    @Override
    public void run() {
        this.lastTimeEnd = System.currentTimeMillis() + TIME_END;
        while (ServerManager.isRunning) {
            try {
                Thread.sleep(2000);
                long remain = this.lastTimeEnd - System.currentTimeMillis();
                if (remain <= 0 && !baotri) {
                    try {
                        quayKetQua();
                    } finally {
                        resetRound();
                    }
                } else if (remain <= 0) {
                    this.lastTimeEnd = System.currentTimeMillis() + TIME_END;
                }
            } catch (InterruptedException e) {
                break;
            } catch (Exception e) {
                e.printStackTrace();
            }
        }
    }

    private void quayKetQua() {
        int d1, d2, d3;

        if (forceDice != null) {
            d1 = clampDoor(forceDice[0]);
            d2 = clampDoor(forceDice[1]);
            d3 = clampDoor(forceDice[2]);
            forceDice = null;
        } else {
            // Nhà cái nghiêng về cửa có ít tiền cược
            int[][] candidates = new int[3][2]; // [door, weight]
            for (int roll = 0; roll < 3; roll++) {
                int pick;
                int minIdx = minTotalDoor();
                if (Util.isTrue(55, 100)) {
                    // 55% ra cửa ít người cược nhất hoặc kề bên nó
                    pick = Util.isTrue(50, 100) ? minIdx : (minIdx + 5) % 6;
                } else {
                    pick = Util.nextInt(0, 5);
                }
                candidates[roll][0] = pick;
            }
            d1 = candidates[0][0];
            d2 = candidates[1][0];
            d3 = candidates[2][0];
        }

        lastDice = new int[]{d1, d2, d3};

        // Đếm số lần trúng mỗi cửa
        int[] matches = new int[6];
        matches[d1]++;
        matches[d2]++;
        matches[d3]++;

        String kqText = doorName((byte) d1) + " : " + doorName((byte) d2) + " : " + doorName((byte) d3);
        listKetQua.add(kqText);
        if (listKetQua.size() > 5) {
            listKetQua.remove(0);
        }

        Set<Player> winners = new HashSet<>();
        StringBuilder winSummary = new StringBuilder();

        // Trả thưởng từng cửa
        for (byte door = 0; door <= 5; door++) {
            int k = matches[door];
            Map<Player, Long> doorBets = bets[door];
            for (Map.Entry<Player, Long> e : doorBets.entrySet()) {
                Player pl = e.getKey();
                long stake = e.getValue() == null ? 0 : e.getValue();
                try {
                    if (stake <= 0 || !isOnline(pl)) continue;
                    if (k > 0) {
                        long reward = stake * (1 + k); // vốn lại + k lần thưởng
                        winners.add(pl);
                        Item tvReward = ItemService.gI().createNewItem(THOI_VANG_TEMPLATE, (int) Math.min(reward, Integer.MAX_VALUE));
                        tvReward.addOptionParam(30, 0);
                        InventoryService.gI().addItemBag(pl, tvReward);
                        InventoryService.gI().sendItemBag(pl);
                        Service.gI().sendMoney(pl);
                        Service.gI().sendThongBaoFromAdmin(pl,
                                "|8|[ Nhà Cái Bầu Cua " + ServerManager.NAME + " ]\n\n|2|" + kqText
                                + "\n|5|Cửa " + doorName(door) + " ra " + k + " lần\n\n|7|Bạn Đã Chiến Thắng\n|1|Số Tiền Nhận Được Là\n|3|"
                                + Util.format(reward) + " Thỏi Vàng");
                    }
                } catch (Exception ignored) {}
            }
        }

        // Thông báo người thua (không trúng cửa nào)
        Set<Player> allBetters = new HashSet<>();
        for (byte door = 0; door <= 5; door++) {
            allBetters.addAll(bets[door].keySet());
        }
        for (Player pl : allBetters) {
            try {
                if (!isOnline(pl) || winners.contains(pl)) continue;
                Service.gI().sendThongBaoFromAdmin(pl,
                        "|8|[ Nhà Cái Bầu Cua " + ServerManager.NAME + " ]\n\n|2|" + kqText
                        + "\n\n|7|Chúc may mắn lần sau!");
            } catch (Exception ignored) {}
        }

        // Chat toàn server nếu có người thắng lớn
        long biggestWin = 0;
        Player biggestWinner = null;
        for (Player w : winners) {
            long totalWin = 0;
            for (byte door = 0; door <= 5; door++) {
                Long st = bets[door].get(w);
                if (st != null && matches[door] > 0) {
                    totalWin += st * matches[door];
                }
            }
            if (totalWin > biggestWin) {
                biggestWin = totalWin;
                biggestWinner = w;
            }
        }
        if (biggestWinner != null && biggestWin >= 50_000) {
            try {
                ChatGlobalService.gI().chat(biggestWinner,
                        biggestWinner.name + " vừa thắng " + Util.format(biggestWin)
                        + " Thỏi Vàng ở Bầu Cua!");
            } catch (Exception ignored) {}
        }
    }

    private int clampDoor(int d) {
        if (d < 0 || d > 5) return 0;
        return d;
    }

    private int minTotalDoor() {
        int minIdx = 0;
        for (int i = 1; i < 6; i++) {
            if (totals[i] < totals[minIdx]) {
                minIdx = i;
            }
        }
        return minIdx;
    }

    private boolean isOnline(Player pl) {
        try {
            return Client.gI().getPlayerByName(pl.name) != null;
        } catch (Exception e) {
            return false;
        }
    }

    private void resetRound() {
        for (int i = 0; i < 6; i++) {
            totals[i] = 0;
            bets[i].clear();
        }
        this.lastTimeEnd = System.currentTimeMillis() + TIME_END;
    }

    public void baoTriAndRefund() {
        baotri = !baotri;
        if (baotri) {
            // Hoàn tất cược đang mở
            for (byte door = 0; door <= 5; door++) {
                for (Map.Entry<Player, Long> e : bets[door].entrySet()) {
                    Player pl = e.getKey();
                    try {
                        if (e.getValue() != null && e.getValue() > 0 && isOnline(pl)) {
                            Item tvRefund = ItemService.gI().createNewItem(THOI_VANG_TEMPLATE, e.getValue().intValue());
                            tvRefund.addOptionParam(30, 0);
                            InventoryService.gI().addItemBag(pl, tvRefund);
                            InventoryService.gI().sendItemBag(pl);
                        }
                    } catch (Exception ignored) {}
                }
                bets[door].clear();
                totals[door] = 0;
            }
            Service.gI().sendThongBaoAllPlayer("Bầu Cua tạm bảo trì, mọi cược đã được hoàn lại!");
        } else {
            lastTimeEnd = System.currentTimeMillis() + TIME_END;
        }
    }
}
