{
  description = "PHP 8.4 development environment with Composer";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";
    flake-utils.url = "github:numtide/flake-utils";
  };

  outputs = { self, nixpkgs, flake-utils }:
    flake-utils.lib.eachDefaultSystem (system:
      let
        pkgs = nixpkgs.legacyPackages.${system};
      in
      {
        devShells.default = pkgs.mkShell {
          buildInputs = with pkgs; [
            php85
            php85Packages.composer
          ];

          shellHook = ''
            echo "PHP 8.4 development environment"
            php --version
            composer --version
          '';
        };
      }
    );
}
