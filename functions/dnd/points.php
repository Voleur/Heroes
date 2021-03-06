<?php
include('../funcs.db.php');

$sql = "SELECT * FROM cms_races WHERE NOT `rSource` = 'PSA' AND NOT `rSource` = 'PSI' AND NOT `rSource` = 'PSK' AND NOT `rSource` = 'PSZ' ORDER BY `rName` ASC";
$stmt = $dbh->prepare($sql);
$stmt->execute();
$Races = $stmt->fetchAll();
$rscores = '';
$Rep = '?';
$Me = '0';
$CL = 0;
$RC = count($Races);
foreach ($Races as $a) {
	$CL = $CL+1;
	$scores = str_replace($Rep, $Me, explode('|', $a['rAbilScore']));
	if ($a['rName'] == "Half-Elf") {
		$rscores .= "\t\t\t".str_replace('_', '', $a['rFullName']).": halfElfDefaultMods";
	} elseif ($a['rFullName'] == 'Human_Variant' ) {
		$rscores .= "\t\t\thumanVariant: humanVariantDefaultMods";
	}else {
		$rscores .= "\t\t\t".lcfirst(str_replace('_', '', $a['rFullName'])).": {str: $scores[0], dex: $scores[1], con: $scores[2], wis: $scores[3], int: $scores[4], cha: $scores[5]}";
	}
	if ($CL == $RC) {$rscores .= "\n";}
	else {$rscores .= ",\n";}
}
header('Content-type: application/javascript');
?>
var DEVOWARE = DEVOWARE || {};

console = console || {
	log: function() {},
	error: function () {},
	warn: function () {},
	assert: function () {},
	dir: function () {},
};

DEVOWARE.encounterPlanner = (function () {
	var attributeCosts = {
			"8": 0,
			"9": 1,
			"10": 2,
			"11": 3,
			"12": 4,
			"13": 5,
			"14": 7,
			"15": 9
	};
	var checkedRacialMods = [];
	var humanVariantDefaultMods = {str: 0, dex: 0, con: 0, wis: 0, int: 0, cha: 0};
	var halfElfDefaultMods = {str: 0, dex: 0, con: 0, wis: 0, int: 0, cha: 2};
	var racialMods = {
			<?php print $rscores; ?>
	};

	var computeTotalCost = function () {
		var i;
		var costFields = $("#point-buy-table").find(".attribute-cost-field");
		var cost = 0;
		for (i = 0; i < costFields.length; i++) {
			cost += parseInt($(costFields[i]).val());
		}
		return cost;
	};

	var createTextField = function (id, value, clazz) {
		return $("<input>", {
			id: "" + id,
			name: "" + id,
			class: clazz,
			type: "text",
			value: "" + value,
			readonly: "true"
		})
	};

	var createSpinnerField = function (id, value, clazz) {
		var spinnerField = $("<div>", {class: "spinner"});
		var inputField = createTextField(id, value, clazz);
		var spinnerBar = $("<div>", {class: "spinner-bar"});
        var upButton = $("<button>", {class: "up-button"});
        var downButton = $("<button>", {class: "down-button"});
		spinnerField.append(inputField);
		spinnerBar.append(upButton);
		spinnerBar.append(downButton);
		spinnerField.append(spinnerBar);
        return {
        	spinnerField: spinnerField,
        	inputField: inputField,
        	upButton: upButton,
        	downButton: downButton
        };
	}

	var resetFields = function () {
		var i, attributeCost, attributeId, modifier;
		var defaultValue = 8, defaultCost = 0;
		var attributeFields = $("#point-buy-table").find(".attribute-base-field");
		var attributeTotals = $("#point-buy-table").find(".attribute-score-field");
		var abilityMods = $("#point-buy-table").find(".attribute-modifier-field");
		var attributeCosts = $("#point-buy-table").find(".attribute-cost-field");
		for (i = 0; i < attributeFields.length; i++) {
			attributeCost = $(attributeCosts[i])
			$(attributeFields[i]).val(defaultValue);
			attributeId = attributeCost.closest(".attribute-row").attr("id");
			racialModifier = getRacialModifier(attributeId);
			$(attributeTotals[i]).val(defaultValue + racialModifier);
			$(abilityMods[i]).val(getAttributeModifier(defaultValue + racialModifier));
			attributeCost.val(defaultCost);
		}
		$("#points-remaining").val($("#points-available").val());
	};

	var generatePointsAvailable = function () {
		var parentDiv = $("#points-available-div");
		var pointsAvailableSpinner = createSpinnerField("points-available-field", 27, "");
        var upButton = pointsAvailableSpinner.upButton;
        var downButton = pointsAvailableSpinner.downButton;
        var pointsAvailableField = pointsAvailableSpinner.inputField;
        var spinnerField = pointsAvailableSpinner.spinnerField;

		upButton.bind("click", function (e) {
			var value = parseInt(pointsAvailableField.val());
			if (value == 54) {
				return false;
			}
			value++;
			pointsAvailableField.val(value);
			resetFields();
			return false;
		});

		downButton.bind("click", function (e) {
			var value = parseInt(pointsAvailableField.val());
			if (value == 0) {
				return false;
			}
			value--;
			pointsAvailableField.val(value);
			resetFields();
			return false;
		});

		parentDiv.append(spinnerField);
	};

	var getRacialModifier = function (attributeId) {
		var race = $("#race-field").val();
		if (race === undefined || race === "") {
		    return 0;
		}
		return racialMods[race][attributeId];
	};

	var getAttributeModifier = function (score) {
		var mod = Math.floor((score - 10) / 2);
		return (mod > 0 ? "+" : "") + mod;
	}

	var generateAttributeFields = function (attributeId, attributeCells) {
		var attributeBaseFieldId = attributeId + "-attribute-base-field";
		var racialModifierFieldId = attributeId + "-racial-modifier-field";
		var attributeScoreFieldId = attributeId + "-attribute-score-field";
		var attributeModifierFieldId = attributeId + "-attribute-modifier-field";
		var attributeCostFieldId = attributeId + "-attribute-cost-field";
        var modifier = getRacialModifier(attributeId);

		var attributeBaseSpinner = createSpinnerField(attributeBaseFieldId, 8, "attribute-base-field");
        var spinnerField = attributeBaseSpinner.spinnerField;
		var attributeBaseField = attributeBaseSpinner.inputField;
		var upButton = attributeBaseSpinner.upButton;
		var downButton = attributeBaseSpinner.downButton;
        var racialModifierField = createTextField(racialModifierFieldId, modifier, "racial-modifier-field");
        var attributeScoreField = createTextField(attributeScoreFieldId, 8 + modifier, "attribute-score-field");
        var attributeModifierField = createTextField(attributeModifierFieldId, -1, "attribute-modifier-field");
        var attributeCostField = createTextField(attributeCostFieldId, 0, "attribute-cost-field");

		var updateFields = function (value) {
			var cost = attributeCosts[value];
			var totalCost = computeTotalCost() - parseInt(attributeCostField.val()) + cost;
			var pointsAvailable = parseInt($("#points-available-field").val());
            if (totalCost > pointsAvailable) {
            	return false;
            }
            var racialModifier = getRacialModifier(attributeId);
            var score = value + racialModifier;
			attributeBaseField.val(value);
			attributeScoreField.val(score);
			attributeModifierField.val(getAttributeModifier(score));
			attributeCostField.val(cost);
			$("#points-remaining-field").val(pointsAvailable - totalCost);
			return false;
		};

		upButton.bind("click", function (e) {
			var value = parseInt(attributeBaseField.val());
			if (value == 15) {
				return false;
			}
			value++;
			return updateFields(value);
		});

		downButton.bind("click", function (e) {
			var value = parseInt(attributeBaseField.val());
			if (value == 8) {
				return false;
			}
			value--;
			return updateFields(value);
		});

		attributeCells.attributeBaseCell.append(spinnerField);
		attributeCells.racialModifierCell.append(racialModifierField);
		attributeCells.attributeScoreCell.append(attributeScoreField);
		attributeCells.attributeModifierCell.append(attributeModifierField);
		attributeCells.attributeCostCell.append(attributeCostField);
	};

	var updateBasedOnRace = function () {
		var race = $("#race-field").val();
		var i, racialModifierField, baseValue, attributeId, attributeModifier;
		var attributeBaseFields = $("#point-buy-table").find(".attribute-base-field");
		var racialModifierFields = $("#point-buy-table").find(".racial-modifier-field");
		var attributeScoreFields = $("#point-buy-table").find(".attribute-score-field");
		var attributeModifierFields = $("#point-buy-table").find(".attribute-modifier-field");
		for (i = 0; i < attributeBaseFields.length; i++) {
			racialModifierField = $(racialModifierFields[i]);
			attributeId = racialModifierField.closest(".attribute-row").attr("id");
			racialModifier = getRacialModifier(attributeId);
			racialModifierField.val(racialModifier);
			baseValue = parseInt($(attributeBaseFields[i]).val());
			$(attributeScoreFields[i]).val(baseValue + racialModifier);
			$(attributeModifierFields[i]).val(getAttributeModifier(baseValue + racialModifier));
		}
	};

	var clearRacialModFields = function () {
		var i;
	    racialMods.humanVariant = humanVariantDefaultMods;
		racialMods.halfElf = halfElfDefaultMods;
		var racialModFields = $("#racial-mod-checkboxes").find(".racial-mod-checkbox-field");
		for (i = 0; i < racialModFields.length; i++) {
			$(racialModFields[i]).attr("checked", false);
		}
		checkedRacialMods = [];
	};

	var handleRaceChange = function () {
		clearRacialModFields();
		var race = $("#race-field").val();
		if (race === "humanVariant" || race === "halfElf") {
			if (race === "halfElf") {
				$("#cha-racial-mod-checkbox").css("visibility", "hidden");
				$("#cha-racial-mod-label").css("visibility", "hidden");
			} else {
				$("#cha-racial-mod-checkbox").css("visibility", "visible");
				$("#cha-racial-mod-label").css("visibility", "visible");
			}
			$("#racial-mod-checkboxes").css("display", "block");
		} else {
			$("#racial-mod-checkboxes").css("display", "none");
		}
		updateBasedOnRace();
	};

	var clone = function (obj) {
		if (null == obj || "object" != typeof obj) return obj;
		var copy = {};
		for (var attr in obj) {
			copy[attr] = obj[attr];
		}
		return copy;
	}

	var handleRacialModFieldChange = function (e) {
		var checkbox, idx, racialModField, mods = null;
		if (this.checked) {
			if (checkedRacialMods.length == 2) {
			    checkbox = checkedRacialMods.pop();
			    checkbox.checked = false;
			}
			checkedRacialMods.push(this);
		} else {
			for (idx = 0; idx < checkedRacialMods.length; idx++) {
				if (this === checkedRacialMods[idx]) {
					break;
				}
			}
			checkedRacialMods.splice(idx, 1);
		}
		var race = $("#race-field").val();
		if (race === "humanVariant") {
			mods = clone(humanVariantDefaultMods);
		} else if (race === "halfElf") {
			mods = clone(halfElfDefaultMods);
		}
		var racialModFields = $("#racial-mod-checkboxes").find(".racial-mod-checkbox-field");
		for (idx = 0; idx < racialModFields.length; idx++) {
			if (racialModFields[idx].checked) {
				mods[racialModFields[idx].value] = 1;
			} else if (race === "humanVariant" || $(racialModFields[idx]).attr("id") !== "cha-racial-mod-checkbox") {
				mods[racialModFields[idx].value] = 0;
			}
		}
		if (race === "humanVariant") {
			racialMods.humanVariant = mods;
		} else if (race === "halfElf") {
			racialMods.halfElf = mods;
		}
		updateBasedOnRace();
	};

	var bindRacialModFields = function () {
		var i;
		var racialModFields = $("#racial-mod-checkboxes").find(".racial-mod-checkbox-field");
		for (i = 0; i < racialModFields.length; i++) {
			$(racialModFields[i]).bind("change", handleRacialModFieldChange);
		}
	};

	var getAttributeCells = function (row) {
		return {
			attributeBaseCell: $(row.find(".attribute-base")),
			racialModifierCell: $(row.find(".racial-modifier")),
			attributeScoreCell: $(row.find(".attribute-score")),
			attributeModifierCell: $(row.find(".attribute-modifier")),
			attributeCostCell: $(row.find(".attribute-cost")),
		};
	};

	var init = function () {
		var i, row, cells;
		generatePointsAvailable();
		var rows = $("#point-buy-table").find(".attribute-row");
		for (i = 0; i < rows.length; i++) {
			row = $(rows[i]);
			cells = getAttributeCells(row);
			generateAttributeFields(row.attr("id"), cells);
		}
		$("#race-field").bind("change", handleRaceChange);
		bindRacialModFields();
	};


	return {
		init: init
	};
})();

$(document).ready(function() {
    DEVOWARE.encounterPlanner.init();
 });