module.exports = {
  branches: [
    'master',
    { name: 'develop', prerelease: 'beta' },
    {
      name: 'feature/+([a-zA-Z0-9_])-+([0-9])*',
      prerelease: '${name.replace(/^feature\\//g, "").split("-", 2).join("")}'
    },
    {
      name: 'release/*',
      prerelease: 'rc',
      plugins: [
        '@semantic-release/release-notes-generator',
        '@semantic-release/changelog'
      ]
    }
  ],
  plugins: [
    [
      '@semantic-release/commit-analyzer',
      {
        "preset": "conventionalcommits",
        "releaseRules": [
          {"breaking": true, "release": "major"},
          {"type": "feat", "release": "minor"},
          {"type": "feature", "release": "minor"},
          {"type": "fix", "release": "patch"},
          {"type": "refactor", "release": "patch"},
          {"type": "break", "release": "major"},
          {"type": "breaking", "release": "major"}

        ]
      }
    ],
    '@semantic-release/git',
    '@semantic-release/gitlab'
  ]
};
