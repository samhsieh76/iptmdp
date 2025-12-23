export default {
    $delay(milliseconds) {
        return new Promise((resolve) => {
            setTimeout(resolve, milliseconds);
        });
    },
    $csrfToken() {
        let meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            return meta.content;
        }
        return "";
    },
    $errorHandler(response) {
        //console.error(response);
    },
    $formatNumberWithComma(number) {
        if (!number) {
            return "-";
        }
        if (number >= 100) {
            return number.toLocaleString();
        } else {
            return number.toString();
        }
    },
    $getToday() {
        let today = new Date();
        let year = today.getFullYear();
        let month = today.getMonth() + 1;
        month = String(month).padStart(2, "0");
        let date = today.getDate();
        date = String(date).padStart(2, "0");
        return `${year}-${month}-${date}`;
    },
    smellyScale() {
        return [
            [10, "清香"],
            [30, "舒適"],
            [50, "正常"],
            [70, "刺鼻"],
            [90, "惡臭"],
        ];
    },
    smellyGradingCriteria() {
        return [
            [10, "清香"],
            [30, "舒適"],
            [70, "正常"],
            [90, "刺鼻"],
            [100, "惡臭"],
        ];
    },
    calcSmellyScore(value) {
        const grading_criteria = this.smellyGradingCriteria();
        for (let i = 0; i < grading_criteria.length; i++) {
            if (value <= grading_criteria[i][0]) {
                return grading_criteria[i][1];
            }
        }
        return "惡臭";
    },
    calcSmellyColor(value) {
        const grading_criteria = [
            [10, "#2FA7CD"],
            [30, "#6ACDAA"],
            [70, "#95BE52"],
            [90, "#FF8D00"],
            [100, "#FF5348"],
        ];
        for (let i = 0; i < grading_criteria.length; i++) {
            if (value <= grading_criteria[i][0]) {
                return grading_criteria[i][1];
            }
        }
        return "#FF5348";
    },
    tempGradingCriteria(value, frontend = false) {
        const temperatureRanges = [
            { max: 8, text: "寒冷", color: frontend ? "#00FFCF" : "#2FA7CD" },
            { max: 13, text: "涼冷", color: frontend ? "#00FFCF" : "#2FA7CD" },
            { max: 18, text: "涼爽", color: frontend ? "#2BF863" : "#95BE52" },
            { max: 23, text: "舒適", color: frontend ? "#2BF863" : "#95BE52" },
            { max: 29, text: "溫暖", color: frontend ? "#FF8D00" : "#FF8D00" },
            { max: 35, text: "暖熱", color: frontend ? "#FF8D00" : "#FF8D00" },
            {
                max: Infinity,
                text: "炎熱",
                color: frontend ? "#FF5348" : "#FF5348",
            },
        ];

        const criteria = temperatureRanges.find(
            (range) => value <= range.max
        ) || {
            text: "-",
            color: "#FF5348",
        };

        return { ...criteria, value };
    },
    humidityGradingCriteria(value, frontend = false) {
        const humidityRanges = [
            { max: 40, text: "乾燥", color: frontend ? "#00FFCF" : "#2FA7CD" },
            { max: 70, text: "合宜", color: frontend ? "#2BF863" : "#95BE52" },
            {
                max: Infinity,
                text: "潮濕",
                color: frontend ? "#FF5348" : "#FF5348",
            },
        ];

        const criteria = humidityRanges.find((range) => value <= range.max) || {
            text: "-",
            color: "#FF5348",
        };

        return { ...criteria, value };
    },
};
