<?php
require_once "../config/database.php";

$category_id = $_GET['category_id'] ?? null;

if ($category_id === null) {
    echo json_encode(["success" => false, "message" => "Kategori ID eksik."]);
    exit;
}

$category_id = (int)$category_id;

// ✅ Üst kategorileri bul (parent zinciri)
function getAllParentCategoryIds($conn, $childId) {
    $ids = [];
    while (true) {
        $stmt = $conn->prepare("SELECT parent_id FROM categories WHERE id = ?");
        $stmt->bind_param("i", $childId);
        $stmt->execute();
        $stmt->bind_result($parent_id);
        if ($stmt->fetch() && $parent_id) {
            $ids[] = $parent_id;
            $childId = $parent_id;
        } else {
            break;
        }
        $stmt->close();
    }
    return $ids;
}

// ✅ Seçilen kategori alt kategori mi?
$isSubcategory = false;
$check = $conn->prepare("SELECT parent_id FROM categories WHERE id = ?");
$check->bind_param("i", $category_id);
$check->execute();
$check->bind_result($parent_id);
if ($check->fetch() && $parent_id) {
    $isSubcategory = true;
}
$check->close();

// ✅ Alt kategori seçildiyse: kendisi + üst kategoriler
// ✅ Ana kategori seçildiyse: sadece kendisi
$allCategoryIds = [$category_id];
if ($isSubcategory) {
    $parentIds = getAllParentCategoryIds($conn, $category_id);
    $allCategoryIds = array_unique(array_merge($allCategoryIds, $parentIds));
}

// ✅ IN (?, ?, ?, ...) oluştur
$placeholders = implode(',', array_fill(0, count($allCategoryIds), '?'));
$sql = "SELECT id, category_id, variant_name, variant_options 
        FROM category_variants 
        WHERE category_id IN ($placeholders)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Sorgu hatası: " . $conn->error]);
    exit;
}

$types = str_repeat("i", count($allCategoryIds));
$stmt->bind_param($types, ...$allCategoryIds);
$stmt->execute();
$result = $stmt->get_result();

$variants = [];

while ($row = $result->fetch_assoc()) {
    $optionsArray = [];

    if (!empty($row['variant_options'])) {
        $options = array_map('trim', explode(',', $row['variant_options']));
        foreach ($options as $opt) {
    $optionsArray[] = [
        "id" => $opt,       // ✅ değer kendisi
        "name" => $opt      // ✅ aynı isimle
    ];
}

    }

    $variants[] = [
        "id" => $row['id'],
        "category_id" => $row['category_id'],
        "name" => $row['variant_name'],
        "options" => $optionsArray
    ];
}

echo json_encode([
    "success" => true,
    "variants" => $variants
]);

$conn->close();
