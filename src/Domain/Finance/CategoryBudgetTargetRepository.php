<?php

declare(strict_types=1);

namespace App\Domain\Finance;

use App\Core\Database;
use PDO;

final class CategoryBudgetTargetRepository
{
    public function __construct(private Database $database)
    {
    }

    public function targets(): array
    {
        $statement = $this->database->connection()->query(
            'SELECT
                categories.id AS category_id,
                categories.name AS category_name,
                category_budget_targets.is_active,
                category_budget_targets.control_type,
                category_budget_targets.target_percent,
                category_budget_targets.desired_amount,
                category_budget_targets.reference_key,
                category_budget_targets.reference_label,
                category_budget_targets.reference_min_percent,
                category_budget_targets.reference_max_percent,
                category_budget_targets.notes
             FROM categories
             LEFT JOIN category_budget_targets ON category_budget_targets.category_id = categories.id
             WHERE categories.is_active = 1
             ORDER BY categories.name'
        );

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function (array $row): array {
            $suggestion = $this->suggestionFor((string) $row['category_name']);

            return [
                ...$row,
                'control_type' => $row['control_type'] ?: $suggestion['control_type'],
                'is_active' => $row['is_active'] === null ? true : ((int) $row['is_active'] === 1),
                'target_percent' => $row['target_percent'] === null ? null : (float) $row['target_percent'],
                'desired_amount' => $row['desired_amount'] === null ? null : (float) $row['desired_amount'],
                'reference_key' => $row['reference_key'] ?: $suggestion['key'],
                'reference_label' => $row['reference_label'] ?: $suggestion['label'],
                'reference_min_percent' => $row['reference_min_percent'] === null ? $suggestion['min'] : (float) $row['reference_min_percent'],
                'reference_max_percent' => $row['reference_max_percent'] === null ? $suggestion['max'] : (float) $row['reference_max_percent'],
                'notes' => $row['notes'] ?: $suggestion['notes'],
                'is_suggested' => $row['control_type'] === null,
            ];
        }, $rows);
    }

    public function baseIncomeAmount(): ?float
    {
        $statement = $this->database->connection()->query(
            'SELECT base_income_amount FROM category_budget_settings WHERE id = 1'
        );
        $value = $statement->fetchColumn();

        return $value === false || $value === null ? null : (float) $value;
    }

    public function saveMany(array $targets, ?int $userId, ?float $baseIncomeAmount): void
    {
        $connection = $this->database->connection();
        $statement = $this->database->connection()->prepare(
            'INSERT INTO category_budget_targets (
                category_id,
                is_active,
                control_type,
                target_percent,
                desired_amount,
                reference_key,
                reference_label,
                reference_min_percent,
                reference_max_percent,
                notes,
                updated_by_user_id
             ) VALUES (
                :category_id,
                :is_active,
                :control_type,
                :target_percent,
                :desired_amount,
                :reference_key,
                :reference_label,
                :reference_min_percent,
                :reference_max_percent,
                :notes,
                :updated_by_user_id
             )
             ON DUPLICATE KEY UPDATE
                is_active = VALUES(is_active),
                control_type = VALUES(control_type),
                target_percent = VALUES(target_percent),
                desired_amount = VALUES(desired_amount),
                reference_key = VALUES(reference_key),
                reference_label = VALUES(reference_label),
                reference_min_percent = VALUES(reference_min_percent),
                reference_max_percent = VALUES(reference_max_percent),
                notes = VALUES(notes),
                updated_by_user_id = VALUES(updated_by_user_id)'
        );

        $settingsStatement = $connection->prepare(
            'INSERT INTO category_budget_settings (id, base_income_amount, updated_by_user_id)
             VALUES (1, :base_income_amount, :updated_by_user_id)
             ON DUPLICATE KEY UPDATE
                base_income_amount = VALUES(base_income_amount),
                updated_by_user_id = VALUES(updated_by_user_id)'
        );

        $connection->beginTransaction();

        try {
            $settingsStatement->execute([
                'base_income_amount' => $baseIncomeAmount,
                'updated_by_user_id' => $userId,
            ]);

            foreach ($targets as $target) {
                $statement->execute([
                    'category_id' => (int) $target['category_id'],
                    'is_active' => (bool) $target['is_active'] ? 1 : 0,
                    'control_type' => $target['control_type'],
                    'target_percent' => $target['target_percent'],
                    'desired_amount' => $target['desired_amount'],
                    'reference_key' => $target['reference_key'],
                    'reference_label' => $target['reference_label'],
                    'reference_min_percent' => $target['reference_min_percent'],
                    'reference_max_percent' => $target['reference_max_percent'],
                    'notes' => $target['notes'],
                    'updated_by_user_id' => $userId,
                ]);
            }

            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }

    public function performance(string $startDate, string $endDate, float $incomeAmount): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT
                categories.id AS category_id,
                categories.name AS category_name,
                category_budget_targets.is_active,
                category_budget_targets.control_type,
                category_budget_targets.target_percent,
                category_budget_targets.desired_amount,
                category_budget_targets.reference_key,
                category_budget_targets.reference_label,
                category_budget_targets.reference_min_percent,
                category_budget_targets.reference_max_percent,
                category_budget_targets.notes,
                COALESCE(SUM(entries.amount), 0) AS spent_amount,
                COUNT(entries.id) AS entries_count
             FROM categories
             LEFT JOIN category_budget_targets ON category_budget_targets.category_id = categories.id
             LEFT JOIN entries ON entries.category_id = categories.id
                AND entries.type = "expense"
                AND entries.entry_date BETWEEN :start_date AND :end_date
             WHERE categories.is_active = 1
               AND COALESCE(category_budget_targets.is_active, 1) = 1
             GROUP BY
                categories.id,
                categories.name,
                category_budget_targets.is_active,
                category_budget_targets.control_type,
                category_budget_targets.target_percent,
                category_budget_targets.desired_amount,
                category_budget_targets.reference_key,
                category_budget_targets.reference_label,
                category_budget_targets.reference_min_percent,
                category_budget_targets.reference_max_percent,
                category_budget_targets.notes
             HAVING spent_amount > 0
                 OR category_budget_targets.target_percent IS NOT NULL
                 OR category_budget_targets.desired_amount IS NOT NULL
             ORDER BY spent_amount DESC, categories.name ASC'
        );
        $statement->execute([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return array_map(function (array $row) use ($incomeAmount): array {
            $suggestion = $this->suggestionFor((string) $row['category_name']);
            $controlType = $row['control_type'] ?: $suggestion['control_type'];
            $targetPercent = $row['target_percent'] === null ? null : (float) $row['target_percent'];
            $desiredAmount = $row['desired_amount'] === null ? null : (float) $row['desired_amount'];
            $spentAmount = (float) $row['spent_amount'];
            $actualPercent = $incomeAmount <= 0 ? null : ($spentAmount / $incomeAmount) * 100;
            $targetAmount = $desiredAmount ?? ($targetPercent === null ? null : $incomeAmount * ($targetPercent / 100));
            $status = $this->status($controlType, $targetAmount, $spentAmount);

            return [
                ...$row,
                'control_type' => $controlType,
                'is_active' => $row['is_active'] === null ? true : ((int) $row['is_active'] === 1),
                'target_percent' => $targetPercent,
                'desired_amount' => $desiredAmount,
                'reference_key' => $row['reference_key'] ?: $suggestion['key'],
                'reference_label' => $row['reference_label'] ?: $suggestion['label'],
                'reference_min_percent' => $row['reference_min_percent'] === null ? $suggestion['min'] : (float) $row['reference_min_percent'],
                'reference_max_percent' => $row['reference_max_percent'] === null ? $suggestion['max'] : (float) $row['reference_max_percent'],
                'notes' => $row['notes'] ?: $suggestion['notes'],
                'spent_amount' => $spentAmount,
                'entries_count' => (int) $row['entries_count'],
                'actual_percent' => $actualPercent,
                'target_amount' => $targetAmount,
                'status' => $status,
                'remaining_amount' => $targetAmount === null ? null : $targetAmount - $spentAmount,
            ];
        }, $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    private function status(string $controlType, ?float $targetAmount, float $spentAmount): string
    {
        if ($targetAmount === null) {
            return 'unconfigured';
        }

        if ($controlType === 'track') {
            return 'tracking';
        }

        if ($controlType === 'minimum') {
            return $spentAmount >= $targetAmount ? 'within' : 'below_minimum';
        }

        return $spentAmount <= $targetAmount ? 'within' : 'above_target';
    }

    public function suggestionFor(string $categoryName): array
    {
        $normalized = $this->normalize($categoryName);

        $rules = [
            'MERCADO' => ['food_home', 'Alimentação em casa / mercado', 'maximum', 10.0, 18.0, 'Referência baseada em consumo essencial. No seu DR, MERCADO representa compras de casa.'],
            'ALIMENTACAO' => ['food_away', 'Alimentação fora de casa', 'maximum', 3.0, 8.0, 'Referência para restaurantes, lanches e delivery.'],
            'ALUGUEL' => ['housing', 'Moradia', 'maximum', 20.0, 30.0, 'Moradia costuma usar referencia de ate 30% da renda.'],
            'AGUA' => ['housing_utilities', 'Moradia / contas basicas', 'maximum', 1.0, 3.0, 'Conta essencial ligada a moradia.'],
            'ENERGIA' => ['housing_utilities', 'Moradia / contas basicas', 'maximum', 3.0, 6.0, 'Conta essencial ligada a moradia.'],
            'GAS' => ['housing_utilities', 'Moradia / contas basicas', 'maximum', 1.0, 3.0, 'Conta essencial ligada a moradia.'],
            'INTERNET' => ['communication', 'Comunicação e internet', 'maximum', 2.0, 5.0, 'Referência para conectividade residencial/profissional.'],
            'TELEFONIA' => ['communication', 'Comunicação e internet', 'maximum', 1.0, 4.0, 'Referência para telefonia e conectividade pessoal.'],
            'COMBUSTIVEL' => ['transport', 'Transporte / veiculo', 'maximum', 5.0, 10.0, 'Parte variavel do custo de transporte.'],
            'MANUT VEICULO' => ['transport', 'Transporte / veiculo', 'maximum', 5.0, 10.0, 'Manutencao deve ser acompanhada junto ao custo total de transporte.'],
            'FINANC VEICULAR' => ['transport_financing', 'Financiamento veicular', 'maximum', 10.0, 15.0, 'Compromisso fixo ligado a transporte.'],
            'IPVA' => ['transport_taxes', 'Impostos veiculares', 'track', null, null, 'Despesa anual/sazonal. Melhor acompanhar do que limitar por percentual mensal fixo.'],
            'SAUDE' => ['health', 'Saúde', 'maximum', 5.0, 10.0, 'Referência para gastos médicos, farmácia e cuidados de saúde.'],
            'EDUCACAO' => ['education', 'Educação', 'maximum', 2.0, 8.0, 'Referência flexível para cursos, escola e desenvolvimento.'],
            'VESTUARIO' => ['apparel', 'Vestuário', 'maximum', 2.0, 5.0, 'Referência para roupas e itens pessoais.'],
            'CUIDADOS PESSOAIS' => ['personal_care', 'Cuidados pessoais', 'maximum', 2.0, 5.0, 'Referência para beleza, higiene e autocuidado.'],
            'DIVERSAO PASSEIO' => ['leisure', 'Lazer / diversão', 'maximum', 5.0, 10.0, 'Referência para lazer, passeios e entretenimento.'],
            'INVESTIMENTO' => ['investment', 'Investimento', 'minimum', 10.0, 20.0, 'Aqui a referência funciona melhor como mínimo desejado, não como limite.'],
            'INVEST MAURICIO' => ['personal_investment', 'Investimento pessoal', 'minimum', 3.0, 10.0, 'Meta minima opcional para projetos/hobbies com carater de investimento pessoal.'],
            'DESP EMPRESA' => ['business', 'Despesa de empresa', 'track', null, null, 'Melhor acompanhar separado das metas pessoais.'],
            'IMPOSTO EMPRESA' => ['business_tax', 'Imposto de empresa', 'track', null, null, 'Despesa operacional/tributaria. Melhor acompanhar separado.'],
            'MEI' => ['business_tax', 'Imposto de empresa', 'track', null, null, 'Despesa operacional/tributaria. Melhor acompanhar separado.'],
            'PET' => ['pets', 'Pet', 'maximum', 2.0, 5.0, 'Referência doméstica para cuidado com pets.'],
            'INFANTIL' => ['children', 'Infantil', 'maximum', 5.0, 12.0, 'Referência flexível para gastos infantis.'],
            'REFORMA MANUT' => ['home_maintenance', 'Reforma / manutenção da casa', 'track', null, null, 'Despesa irregular. Melhor acompanhar por período e planejar quando houver obra.'],
        ];

        foreach ($rules as $match => $rule) {
            if (str_contains($normalized, $match)) {
                return $this->reference(...$rule);
            }
        }

        return $this->reference('custom', 'Sem referencia definida', 'track', null, null, 'Categoria sem parametro teorico mapeado. Use acompanhamento ou defina uma meta propria.');
    }

    private function reference(string $key, string $label, string $controlType, ?float $min, ?float $max, string $notes): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'control_type' => $controlType,
            'min' => $min,
            'max' => $max,
            'notes' => $notes,
        ];
    }

    private function normalize(string $value): string
    {
        $value = mb_strtoupper($value, 'UTF-8');
        $value = strtr($value, [
            'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C',
        ]);

        return trim((string) preg_replace('/[^\pL\pN]+/u', ' ', $value));
    }
}
