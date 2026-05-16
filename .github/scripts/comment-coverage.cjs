const fs = require('fs');

function readJson(filePath) {
  try {
    return JSON.parse(fs.readFileSync(filePath, 'utf8'));
  } catch (error) {
    return null;
  }
}

function parseCloverMetrics(filePath) {
  try {
    const xml = fs.readFileSync(filePath, 'utf8');

    // We want the total project metrics, which in clover.xml is usually the last <metrics> tag
    // inside the <project> tag. We can isolate the <project> metrics by looking for the
    // project node or just extracting all matches and taking the last one.
    const statementsMatches = [...xml.matchAll(/statements="(\d+)"/g)];
    const coveredStatementsMatches = [...xml.matchAll(/coveredstatements="(\d+)"/g)];

    if (statementsMatches.length > 0 && coveredStatementsMatches.length > 0) {
      const total = parseInt(statementsMatches[statementsMatches.length - 1][1], 10);
      const covered = parseInt(coveredStatementsMatches[coveredStatementsMatches.length - 1][1], 10);
      return total > 0 ? ((covered / total) * 100).toFixed(2) : 0;
    }
  } catch (error) {
    return null;
  }
  return null;
}

function generateWebReport(summaryPath) {
  const summary = readJson(summaryPath);
  if (!summary || !summary.total) {
    return `### Frontend (Web)\n\n❌ No coverage data found (Tests likely failed).\n`;
  }

  const total = summary.total;
  const metrics = ['lines', 'statements', 'functions', 'branches'];

  let report = `### Frontend (Alpine.js / Vitest)\n\n`;
  report += `| Metric | Coverage |\n`;
  report += `| :--- | :---: |\n`;

  metrics.forEach(metric => {
    const current = total[metric].pct;
    report += `| ${metric.charAt(0).toUpperCase() + metric.slice(1)} | ${current}% |\n`;
  });

  return report;
}

function generateApiReport(cloverPath) {
  const percentage = parseCloverMetrics(cloverPath);

  if (percentage === null) {
     return `### Backend (Laravel)\n\n❌ No coverage data found (Tests likely failed).\n`;
  }

  let report = `### Backend (Laravel / PHPUnit)\n\n`;
  report += `| Metric | Coverage |\n`;
  report += `| :--- | :---: |\n`;
  report += `| Statements | ${percentage}% |\n`;

  return report;
}

module.exports = async ({ github, context }) => {
  const webSummaryPath = 'coverage/coverage-summary.json';
  const apiCloverPath = 'coverage.xml';

  const webReport = generateWebReport(webSummaryPath);
  const apiReport = generateApiReport(apiCloverPath);

  let body = `## 📊 Test Coverage Report\n\n`;
  body += webReport + '\n';
  body += apiReport + '\n';

  // Post the comment
  if (context.payload.pull_request) {
    await github.rest.issues.createComment({
      owner: context.repo.owner,
      repo: context.repo.repo,
      issue_number: context.payload.pull_request.number,
      body: body
    });
  }
};
