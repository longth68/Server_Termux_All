package nro.virtualplayer;

import nro.virtualplayer.core.VirtualMemory;
import nro.virtualplayer.core.VirtualPersonality;
import nro.virtualplayer.core.VirtualProfile;
import Utils.Logger;
import org.json.simple.JSONArray;
import org.json.simple.JSONObject;
import org.json.simple.parser.JSONParser;

import java.io.File;
import java.io.FileReader;
import java.io.FileWriter;
import java.util.Map;

/**
 * Lưu / tải trạng thái Virtual Player khi server restart.
 * PHASE 6 - Persistence.
 *
 * Không reset: level, exp, gold, inventory, equipment, quest, ranking,
 * relationship, memory, progression.
 *
 * Dữ liệu lưu tại data/virtualplayer_save.json (JSON, dễ đọc/sửa).
 */
public class VirtualPersistence {

    private static final String SAVE_FILE = "data/virtualplayer_save.json";

    private VirtualPersistence() {}

    /**
     * Lưu toàn bộ Virtual Player hiện tại ra file JSON.
     */
    public static synchronized void saveAll() {
        try {
            JSONArray arr = new JSONArray();
            for (VirtualPlayer vp : VirtualPlayerManager.gI().getBots()) {
                JSONObject o = new JSONObject();
                o.put("name", vp.name);
                o.put("gender", vp.gender);
                o.put("head", vp.head);
                o.put("body", vp.body_);
                o.put("leg", vp.leg_);

                // Stats & progression
                if (vp.nPoint != null) {
                    o.put("power", vp.nPoint.power);
                    o.put("hpg", vp.nPoint.hpg);
                    o.put("dameg", vp.nPoint.dameg);
                    o.put("defg", vp.nPoint.defg);
                }
                if (vp.inventory != null) {
                    o.put("gold", vp.inventory.gold);
                }
                o.put("state", vp.state.name());
                o.put("lastMapId", vp.zone != null && vp.zone.map != null ? vp.zone.map.mapId : -1);

                // Profile & personality
                if (vp.profile != null) {
                    JSONArray pers = new JSONArray();
                    for (VirtualPersonality p : vp.profile.getPersonalities()) {
                        pers.add(p.name());
                    }
                    o.put("personalities", pers);
                    o.put("talkativeness", vp.profile.getTalkativeness());
                    o.put("riskTolerance", vp.profile.getRiskTolerance());
                    o.put("helpfulness", vp.profile.getHelpfulness());
                    o.put("competitiveness", vp.profile.getCompetitiveness());
                    o.put("laziness", vp.profile.getLaziness());
                    o.put("greed", vp.profile.getGreed());
                    o.put("catchupPercent", vp.profile.catchupPercent);
                }

                // Memory & relationships
                if (vp.memory != null) {
                    JSONObject rels = new JSONObject();
                    for (Map.Entry<String, Float> e : vp.memory.getRelationScores().entrySet()) {
                        rels.put(e.getKey(), e.getValue());
                    }
                    o.put("relations", rels);
                }

                arr.add(o);
            }

            File f = new File(SAVE_FILE);
            f.getParentFile().mkdirs();
            try (FileWriter fw = new FileWriter(f)) {
                fw.write(arr.toJSONString());
            }
        } catch (Exception e) {
            Logger.logException(VirtualPersistence.class, e);
        }
    }

    /**
     * Tải danh sách thông tin bot đã lưu để tái tạo lại (nếu có).
     * Trả về JSONArray raw để VirtualPlayerManager tự build.
     */
    public static synchronized JSONArray loadAll() {
        try {
            File f = new File(SAVE_FILE);
            if (!f.exists()) return null;
            try (FileReader fr = new FileReader(f)) {
                Object parsed = new JSONParser().parse(fr);
                if (parsed instanceof JSONArray) {
                    return (JSONArray) parsed;
                }
            }
        } catch (Exception e) {
            Logger.logException(VirtualPersistence.class, e);
        }
        return null;
    }
}