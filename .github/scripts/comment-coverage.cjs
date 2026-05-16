const fs = require('fs');

function readJson(filePath) {
  try {
    return JSON.parse(fs.readFileSync(filePath, 'utf8'));
  } catch (error) {
    return null;
  }
}

function extractThresholds(configPath) {
  try {
    const content = fs.readFileSync(configPath, 'utf8');
    const linesMatch = content.match(/(?:lines|statements|functions|branches)['"]?\s*:\s*(\d+(\.\d+)?)/g);

    const thresholds = { lines: 0, statements: 0, functions: 0, branches: 0 };

    if (linesMatch) {
      linesMatch.forEach(match => {
        const [key, value] = match.split(':').map(s => s.trim().replace(/['"]/g, ''));
        if (thresholds.hasOwnProperty(key)) {
          thresholds[key] = parseFloat(value);
        }
      });
    }

    return thresholds;
  } catch (e) {
    return { lines: 0, statements: 0, functions: 0, branches: 0 };
  }
}

function parseCloverMetrics(filePath) {
  try {
    const xml = fs.readFileSync(filePath, 'utf8');

    // We want the total project metrics, which in clover.xml is usually the last <metrics> tag
    const extractMetric = (metricName) => {
      // The regex needs to distinguish between "statements" and "coveredstatements"
      // Using word boundary or specific preceding space is better
      const regex = new RegExp(`(?:\\s|^)${metricName}="(\\d+)"`, 'g');
      const matches = [...xml.matchAll(regex)];
      if (matches.length > 0) {
        return parseInt(matches[matches.length - 1][1], 10);
      }
      return 0;
    };

    const statements = extractMetric('statements');
    const coveredstatements = extractMetric('coveredstatements');
    const methods = extractMetric('methods');
    const coveredmethods = extractMetric('coveredmethods');
    const elements = extractMetric('elements');
    const coveredelements = extractMetric('coveredelements');

    const calculatePct = (covered, total) => total > 0 ? ((covered / total) * 100).toFixed(2) : '0.00';

    return {
      statements: calculatePct(coveredstatements, statements),
      methods: calculatePct(coveredmethods, methods),
      elements: calculatePct(coveredelements, elements),
    };
  } catch (error) {
    return null;
  }
}

function generateWebReport(summaryPath, configPath) {
  const summary = readJson(summaryPath);
  if (!summary || !summary.total) {
    return { report: `### Frontend (Alpine.js / Vitest)\n\n❌ No coverage data found (Tests likely failed).\n`, improved: false, failed: true };
  }

  const total = summary.total;
  const thresholds = extractThresholds(configPath);
  const metrics = ['lines', 'statements', 'functions', 'branches'];

  let report = `### Frontend (Alpine.js / Vitest)\n\n`;
  report += `| Metric | Current | Minimum | Status |\n`;
  report += `| :--- | :---: | :---: | :--- |\n`;

  let improved = false;
  let failed = false;

  metrics.forEach(metric => {
    const current = total[metric].pct;
    const min = thresholds[metric];

    let icon = '✅';
    let statusText = 'Stable';

    if (current < min) {
      icon = '❌';
      statusText = `**Failed** (< ${min}%)`;
      failed = true;
    } else if (Math.floor(current) > min) {
      icon = '🎉';
      statusText = `**Improved** (> ${min}%)`;
      improved = true;
    }

    report += `| ${metric.charAt(0).toUpperCase() + metric.slice(1)} | ${current}% | ${min}% | ${icon} ${statusText} |\n`;
  });

  return { report, improved, failed };
}

function generateApiReport(cloverPath) {
  const metrics = parseCloverMetrics(cloverPath);

  if (metrics === null) {
     return { report: `### Backend (Laravel / PHPUnit)\n\n❌ No coverage data found (Tests likely failed).\n`, improved: false, failed: true };
  }

  // Default minimum for backend if not specified elsewhere
  const min = 80;

  let report = `### Backend (Laravel / PHPUnit)\n\n`;
  report += `| Metric | Current | Minimum | Status |\n`;
  report += `| :--- | :---: | :---: | :--- |\n`;

  const metricMap = {
    'Statements': parseFloat(metrics.statements),
    'Methods': parseFloat(metrics.methods),
    'Elements': parseFloat(metrics.elements)
  };

  let improved = false;
  let failed = false;

  for (const [name, current] of Object.entries(metricMap)) {
    let icon = '✅';
    let statusText = 'Stable';

    if (current < min) {
      icon = '❌';
      statusText = `**Failed** (< ${min}%)`;
      failed = true;
    } else if (Math.floor(current) > min) {
      icon = '🎉';
      statusText = `**Improved** (> ${min}%)`;
      improved = true;
    }

    report += `| ${name} | ${current}% | ${min}% | ${icon} ${statusText} |\n`;
  }

  return { report, improved, failed };
}

module.exports = async ({ github, context }) => {
  const webSummaryPath = 'coverage/coverage-summary.json';
  const apiCloverPath = 'coverage.xml';
  const webConfigPath = 'vite.config.js';

  const webResult = generateWebReport(webSummaryPath, webConfigPath);
  const apiResult = generateApiReport(apiCloverPath);

  let body = `## 📊 Test Coverage Report\n\n`;
  body += webResult.report + '\n';
  body += apiResult.report + '\n';

  if (webResult.improved || apiResult.improved) {
    body += `\n### 🚀 Improvements Detected!\n`;
    body += `Congratulations! The coverage has increased above the minimum threshold.\n`;
    body += `**Action Required:** Please update the configuration files to lock in these gains and prevent future regressions.`;
  }

  if (webResult.failed || apiResult.failed) {
    body += `\n### ⚠️ Regressions Detected\n`;
    body += `Some coverage metrics have dropped below the allowed minimum. Please check the tables above.`;
  }

  // Post the comment
  if (context.payload && context.payload.pull_request) {
    await github.rest.issues.createComment({
      owner: context.repo.owner,
      repo: context.repo.repo,
      issue_number: context.payload.pull_request.number,
      body: body
    });
  } else {
    // For local testing
    console.log(body);
  }
};
